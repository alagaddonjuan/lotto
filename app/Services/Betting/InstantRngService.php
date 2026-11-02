<?php

namespace App\Services\Betting;

use App\Models\BetTicket;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstantRngService
{
    /**
     * Generate CSPRNG unique random numbers using random_int().
     *
     * @param int $count Number of balls to draw
     * @param int $min Minimum ball number (e.g. 1)
     * @param int $max Maximum ball number (e.g. 80)
     * @return array<int>
     */
    public function generateDrawNumbers(int $count, int $min = 1, int $max = 80): array
    {
        $numbers = [];
        while (count($numbers) < $count) {
            $num = random_int($min, $max);
            if (!in_array($num, $numbers, true)) {
                $numbers[] = $num;
            }
        }

        sort($numbers);
        return $numbers;
    }

    /**
     * Evaluate and settle an instant game ticket immediately.
     */
    public function settleInstantTicket(BetTicket $ticket, array $drawnNumbers): array
    {
        return DB::transaction(function () use ($ticket, $drawnNumbers) {
            $lockedTicket = BetTicket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();
            $player = Player::where('id', $lockedTicket->player_id)->lockForUpdate()->firstOrFail();

            $selected = (array) $lockedTicket->selected_numbers;
            $matches = array_values(array_intersect($selected, $drawnNumbers));
            $hitCount = count($matches);

            // Compute multiplier based on hit count for Keno
            $multiplier = $this->calculateKenoMultiplier(count($selected), $hitCount);
            $payoutKobo = (int) round($lockedTicket->stake_kobo * $multiplier);

            $status = $payoutKobo > 0 ? 'WON' : 'LOST';

            $lockedTicket->update([
                'lifecycle_status' => $status,
                'payout_kobo' => $payoutKobo,
                'payout_amount' => round($payoutKobo / 100, 2),
                'status' => $status === 'WON' ? 'won' : 'settled',
                'raw_operator_response' => array_merge((array)$lockedTicket->raw_operator_response, [
                    'drawn_numbers' => $drawnNumbers,
                    'matches' => $matches,
                    'hit_count' => $hitCount,
                    'multiplier' => $multiplier,
                    'settled_at' => now()->toIso8601String(),
                ]),
            ]);

            // If ticket won, credit player's real cash balance
            if ($payoutKobo > 0) {
                $player->cash_balance_kobo += $payoutKobo;
                $player->save();
            }

            Log::info("[InstantRNG] Ticket #{$lockedTicket->ticket_reference} settled as {$status}. Hits: {$hitCount}, Payout: NGN " . ($payoutKobo / 100));

            return [
                'status' => $status,
                'drawn_numbers' => $drawnNumbers,
                'matches' => $matches,
                'hit_count' => $hitCount,
                'payout_kobo' => $payoutKobo,
                'payout_amount' => round($payoutKobo / 100, 2),
            ];
        });
    }

    /**
     * Standard Keno paytable multiplier.
     */
    protected function calculateKenoMultiplier(int $picks, int $hits): float
    {
        if ($picks === 10) {
            return match ($hits) {
                10 => 100000.0,
                9 => 5000.0,
                8 => 500.0,
                7 => 80.0,
                6 => 20.0,
                5 => 3.0,
                0 => 5.0, // Catch 0 bonus
                default => 0.0,
            };
        }

        if ($picks === 5) {
            return match ($hits) {
                5 => 500.0,
                4 => 50.0,
                3 => 5.0,
                default => 0.0,
            };
        }

        // Direct 1 to 4 picks
        if ($hits === $picks && $picks > 0) {
            return match ($picks) {
                1 => 3.0,
                2 => 14.0,
                3 => 65.0,
                4 => 240.0,
                default => 2.0,
            };
        }

        return 0.0;
    }
}

