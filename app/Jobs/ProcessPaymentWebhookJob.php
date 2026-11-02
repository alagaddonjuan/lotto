<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $provider,
        public string $reference,
        public int $amountKobo,
        public string $phone,
        public array $payload,
        public ?int $tenantId = null
    ) {
        $this->onQueue('webhooks-payment');
    }

    public function handle(WhatsAppManager $whatsAppManager): void
    {
        // 1. Webhook Idempotency Pre-Check
        $existingEvent = WebhookEvent::where('provider', $this->provider)
            ->where('external_reference', $this->reference)
            ->first();

        if ($existingEvent && $existingEvent->processed_at !== null) {
            Log::info("[PaymentWebhook] Idempotent skip: {$this->provider} reference {$this->reference} already processed.");
            return;
        }

        $tenant = $this->tenantId ? Tenant::find($this->tenantId) : Tenant::first();
        if (!$tenant) {
            Log::error("[PaymentWebhook] No active tenant found for payment {$this->reference}");
            return;
        }

        try {
            DB::transaction(function () use ($tenant) {
                // 2. Insert into webhook_events with unique constraint enforcement
                $event = WebhookEvent::firstOrCreate(
                    [
                        'provider' => $this->provider,
                        'external_reference' => $this->reference,
                    ],
                    [
                        'payload' => $this->payload,
                        'processed_at' => null,
                    ]
                );

                if ($event->processed_at !== null) {
                    return;
                }

                // 3. Pessimistic lock on Player record
                $player = Player::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'phone' => $this->phone],
                    [
                        'name' => 'Player ' . substr($this->phone, -4),
                        'cash_balance_kobo' => 0,
                        'bonus_balance_kobo' => 0,
                        'last_active_at' => now(),
                    ]
                );

                $lockedPlayer = Player::where('id', $player->id)->lockForUpdate()->first();

                // 4. Credit Real Bank Cash Deposit in Kobo
                $lockedPlayer->cash_balance_kobo += $this->amountKobo;
                $lockedPlayer->last_active_at = now();
                $lockedPlayer->save();

                // 5. Mark webhook processed
                $event->update(['processed_at' => now()]);
            });
        } catch (QueryException $e) {
            // Handle unique constraint collision under high concurrency
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                Log::info("[PaymentWebhook] Handled concurrent duplicate insertion for {$this->reference}");
                return;
            }
            throw $e;
        }

        // 6. Send instant WhatsApp receipt
        try {
            $gateway = $whatsAppManager->driver($tenant);
            $amountNgn = number_format($this->amountKobo / 100, 2);
            $freshPlayer = Player::where('tenant_id', $tenant->id)->where('phone', $this->phone)->first();
            $newBalNgn = number_format(($freshPlayer?->cash_balance_kobo ?? 0) / 100, 2);

            $receipt = "✅ *DEPOSIT CONFIRMED (REAL CASH)*\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "💰 Deposited: *{$tenant->currency} {$amountNgn}*\n"
                     . "💳 Ref: *{$this->reference}*\n"
                     . "🏦 Cash Wallet: *{$tenant->currency} {$newBalNgn}*\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n\n"
                     . "Ready to play! Reply *1* to pick today's winning numbers. 🍀";

            $gateway->sendTextMessage($this->phone, $receipt, $tenant->whatsapp_number);
        } catch (\Throwable $e) {
            Log::warning("[PaymentWebhook] Failed to dispatch WhatsApp receipt: {$e->getMessage()}");
        }
    }
}

