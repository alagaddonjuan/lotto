<?php

namespace App\Jobs;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppendCryptographicLedgerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    public function __construct(public BetTicket $ticket)
    {
        // Strictly assign to single-threaded serialized audit queue
        $this->onQueue('ledger-serial');
    }

    /**
     * Execute the job with a serialized atomic lock.
     */
    public function handle(): void
    {
        // Promotional/Bonus funds do not enter the Executive Clearinghouse commercial split ledger
        if ($this->ticket->wallet_source !== 'CASH') {
            Log::info("[Ledger] Skipping ledger append for bonus ticket #{$this->ticket->id}");
            return;
        }

        $lock = Cache::lock('audit_chain_lock', 5);

        $lock->block(10, function () {
            DB::transaction(function () {
                $ticket = $this->ticket->fresh();
                $tenant = $ticket->tenant;

                // Check if already processed
                $existing = LedgerEntry::where('bet_ticket_id', $ticket->id)->exists();
                if ($existing) {
                    return;
                }

                $secret = config('app.key') ?: 'q4i-clearinghouse-secret-key-35-65';

                // Fetch latest ledger entry in tenant's chain with lock
                $latestEntry = LedgerEntry::where('tenant_id', $tenant->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $prevHash = $latestEntry?->hash ?? str_repeat('0', 64);
                $timestamp = now()->toIso8601String();

                $q4iShareKobo = $ticket->q4i_commission_kobo;
                $operatorShareKobo = $ticket->operator_pool_kobo;

                // Amount in NGN for backward compatible ledger display
                $q4iAmount = round($q4iShareKobo / 100, 2);
                $operatorAmount = round($operatorShareKobo / 100, 2);

                // 1. Q4I Commission Entry (35%)
                $q4iPayload = implode('|', [
                    $tenant->id,
                    $ticket->id,
                    'Q4I_COMMISSION',
                    $q4iShareKobo,
                    $tenant->currency,
                    $prevHash,
                    $timestamp,
                ]);
                $q4iHash = hash_hmac('sha256', $q4iPayload, $secret);

                LedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'bet_ticket_id' => $ticket->id,
                    'entry_type' => 'Q4I_COMMISSION',
                    'debit_account' => 'PLAYER_STAKE_ESCROW',
                    'credit_account' => 'Q4I_EARNED_REVENUE',
                    'amount' => $q4iAmount,
                    'currency' => $tenant->currency,
                    'previous_hash' => $prevHash,
                    'hash' => $q4iHash,
                    'metadata' => [
                        'stake_kobo' => $ticket->stake_kobo,
                        'q4i_commission_kobo' => $q4iShareKobo,
                        'ticket_ref' => $ticket->ticket_reference,
                        'wallet_source' => 'CASH',
                    ],
                    'created_at' => now(),
                ]);

                // 2. Operator Game Pool Entry (65%)
                $operatorPayload = implode('|', [
                    $tenant->id,
                    $ticket->id,
                    'OPERATOR_POOL',
                    $operatorShareKobo,
                    $tenant->currency,
                    $q4iHash,
                    $timestamp,
                ]);
                $operatorHash = hash_hmac('sha256', $operatorPayload, $secret);

                LedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'bet_ticket_id' => $ticket->id,
                    'entry_type' => 'OPERATOR_POOL',
                    'debit_account' => 'PLAYER_STAKE_ESCROW',
                    'credit_account' => 'OPERATOR_GAME_POOL',
                    'amount' => $operatorAmount,
                    'currency' => $tenant->currency,
                    'previous_hash' => $q4iHash,
                    'hash' => $operatorHash,
                    'metadata' => [
                        'stake_kobo' => $ticket->stake_kobo,
                        'operator_pool_kobo' => $operatorShareKobo,
                        'ticket_ref' => $ticket->ticket_reference,
                        'wallet_source' => 'CASH',
                    ],
                    'created_at' => now(),
                ]);

                // Seal ticket with the final HMAC-SHA256 block hash
                $ticket->update(['hash' => $operatorHash]);
            });
        });
    }
}

