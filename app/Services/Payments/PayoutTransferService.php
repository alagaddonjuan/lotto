<?php

namespace App\Services\Payments;

use App\Models\Player;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayoutTransferService
{
    /**
     * Disburse winnings or withdrawal to player's bank account.
     */
    public function disbursePayout(Player $player, int $amountKobo, string $recipientCode, string $reference): array
    {
        $amountNgn = round($amountKobo / 100, 2);

        // In local/sandbox/mock environment, generate mock transfer confirmation
        if (app()->environment('local', 'testing') || empty(config('services.paystack.secret_key'))) {
            Log::info("[PayoutTransfer] Mock disbursement dispatched to Player #{$player->id} for NGN {$amountNgn} (Ref: {$reference})");
            return [
                'success' => true,
                'transfer_code' => 'TRF_' . uniqid(),
                'status' => 'success',
                'amount_kobo' => $amountKobo,
                'amount_ngn' => $amountNgn,
                'reference' => $reference,
            ];
        }

        try {
            $response = Http::withToken(config('services.paystack.secret_key'))
                ->timeout(10)
                ->post('https://api.paystack.co/transfer', [
                    'source' => 'balance',
                    'amount' => $amountKobo,
                    'recipient' => $recipientCode,
                    'reason' => "Q4I Clearinghouse Settlement Payout Ref {$reference}",
                    'reference' => $reference,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'transfer_code' => (string) $response->json('data.transfer_code'),
                    'status' => (string) $response->json('data.status'),
                    'amount_kobo' => $amountKobo,
                    'amount_ngn' => $amountNgn,
                    'reference' => $reference,
                ];
            }

            Log::error('[PayoutTransfer] Gateway transfer error', ['body' => $response->json()]);
            return [
                'success' => false,
                'message' => $response->json('message', 'Bank transfer gateway error'),
            ];
        } catch (\Throwable $e) {
            Log::error("[PayoutTransfer] Transfer exception: {$e->getMessage()}");
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

