<?php

namespace App\Services\Betting;

use App\Jobs\AppendCryptographicLedgerJob;
use App\Models\BetTicket;
use App\Models\GameDraw;
use App\Models\Player;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceBetService
{
    /**
     * Place an institutional bet ticket with pessimistic locking and dual-wallet Kobo math.
     *
     * @param Player $player
     * @param GameDraw $gameDraw
     * @param array $selectedNumbers
     * @param int $stakeKobo (e.g. 10000 Kobo = 100.00 NGN)
     * @param string $walletSource 'CASH' | 'BONUS'
     * @param string|null $idempotencyKey
     * @return BetTicket
     */
    public function placeBet(
        Player $player,
        GameDraw $gameDraw,
        array $selectedNumbers,
        int $stakeKobo,
        string $walletSource = 'CASH',
        ?string $idempotencyKey = null
    ): BetTicket {
        if ($stakeKobo <= 0) {
            throw new DomainException('Stake amount in Kobo must be strictly positive.');
        }

        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();

        // Check for existing idempotent ticket
        $existing = BetTicket::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($player, $gameDraw, $selectedNumbers, $stakeKobo, $walletSource, $idempotencyKey) {
            // 1. Lock and validate GameDraw
            $lockedDraw = GameDraw::where('id', $gameDraw->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDraw->status !== GameDraw::STATUS_OPEN) {
                throw new DomainException("Cannot place bet: Draw #{$lockedDraw->draw_code} is currently {$lockedDraw->status}.");
            }

            if (now()->gte($lockedDraw->closes_at)) {
                throw new DomainException("Cannot place bet: Draw #{$lockedDraw->draw_code} closed at {$lockedDraw->closes_at->toIso8601String()}.");
            }

            // 2. Lock and validate Player dual wallet
            $lockedPlayer = Player::where('id', $player->id)
                ->lockForUpdate()
                ->firstOrFail();

            $q4iCommissionKobo = 0;
            $operatorPoolKobo = 0;

            if ($walletSource === 'CASH') {
                if ($lockedPlayer->cash_balance_kobo < $stakeKobo) {
                    $needed = number_format($stakeKobo / 100, 2);
                    $has = number_format($lockedPlayer->cash_balance_kobo / 100, 2);
                    throw new DomainException("Insufficient cash balance: NGN {$has} available, NGN {$needed} required.");
                }

                // Debit cash balance
                $lockedPlayer->cash_balance_kobo -= $stakeKobo;

                // 35% Q4I Clearinghouse Commission / 65% Operator Game Pool
                $q4iCommissionKobo = (int) round($stakeKobo * 0.35);
                $operatorPoolKobo = $stakeKobo - $q4iCommissionKobo;
            } elseif ($walletSource === 'BONUS') {
                if ($lockedPlayer->bonus_balance_kobo < $stakeKobo) {
                    $needed = number_format($stakeKobo / 100, 2);
                    $has = number_format($lockedPlayer->bonus_balance_kobo / 100, 2);
                    throw new DomainException("Insufficient bonus balance: NGN {$has} available, NGN {$needed} required.");
                }

                // Debit promotional bonus balance
                $lockedPlayer->bonus_balance_kobo -= $stakeKobo;

                // Promotional trial funds NEVER split into the executive ledger
                $q4iCommissionKobo = 0;
                $operatorPoolKobo = 0;
            } else {
                throw new DomainException("Invalid wallet source: {$walletSource}. Must be CASH or BONUS.");
            }

            $lockedPlayer->last_active_at = now();
            $lockedPlayer->save();

            // 3. Create BetTicket record
            $ticketRef = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(8));

            $ticket = BetTicket::create([
                'tenant_id' => $lockedDraw->tenant_id,
                'player_id' => $lockedPlayer->id,
                'game_draw_id' => $lockedDraw->id,
                'ticket_reference' => $ticketRef,
                'operator_ticket_id' => 'OP-' . strtoupper(Str::random(8)),
                'game_code' => $lockedDraw->lottoGame?->game_code ?? '5_90_DAILY',
                'selected_numbers' => $selectedNumbers,
                'wallet_source' => $walletSource,
                'stake_kobo' => $stakeKobo,
                'q4i_commission_kobo' => $q4iCommissionKobo,
                'operator_pool_kobo' => $operatorPoolKobo,
                'payout_kobo' => 0,
                'stake_amount' => round($stakeKobo / 100, 2),
                'q4i_share' => round($q4iCommissionKobo / 100, 2),
                'operator_share' => round($operatorPoolKobo / 100, 2),
                'status' => 'accepted',
                'lifecycle_status' => 'OPEN',
                'payout_amount' => 0.00,
                'idempotency_key' => $idempotencyKey,
            ]);

            // 4. Dispatch HMAC-SHA256 audit chaining job (strictly for CASH stakes)
            if ($walletSource === 'CASH') {
                if (config('queue.default') === 'sync') {
                    AppendCryptographicLedgerJob::dispatchSync($ticket);
                } else {
                    AppendCryptographicLedgerJob::dispatch($ticket)->onQueue('ledger-serial');
                }
            }

            return $ticket;
        });
    }
}

