<?php

namespace App\Jobs;

use App\Models\BetTicket;
use App\Models\GameDraw;
use App\Models\Player;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettleDrawTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public GameDraw $gameDraw)
    {
        $this->onQueue('default');
    }

    public function handle(WhatsAppManager $whatsAppManager): void
    {
        $draw = $this->gameDraw->fresh();

        if ($draw->status !== GameDraw::STATUS_RESULT_PENDING && $draw->status !== GameDraw::STATUS_LOCKED) {
            Log::warning("[Settlement] Cannot settle draw #{$draw->id} in status {$draw->status}.");
            return;
        }

        $winningNumbers = (array) $draw->winning_numbers;
        if (empty($winningNumbers)) {
            Log::error("[Settlement] Cannot settle draw #{$draw->id}: winning numbers not set.");
            return;
        }

        $tickets = BetTicket::where('game_draw_id', $draw->id)
            ->where('lifecycle_status', 'OPEN')
            ->get();

        Log::info("[Settlement] Commencing settlement for draw #{$draw->draw_code} with " . count($tickets) . " open ticket(s).");

        $tenant = $draw->tenant;
        $gateway = $whatsAppManager->driver($tenant);

        foreach ($tickets as $ticket) {
            DB::transaction(function () use ($ticket, $winningNumbers, $tenant, $gateway, $draw) {
                $lockedTicket = BetTicket::where('id', $ticket->id)->lockForUpdate()->first();
                if ($lockedTicket->lifecycle_status !== 'OPEN') {
                    return;
                }

                $selected = (array) $lockedTicket->selected_numbers;
                $matches = array_values(array_intersect($selected, $winningNumbers));
                $requiredPicks = count($selected);

                // Standard 5/90 Direct rule: all chosen numbers must hit
                $isWinner = (count($matches) === $requiredPicks && $requiredPicks > 0);

                $multiplier = match ($requiredPicks) {
                    1 => 40.0,
                    2 => 240.0,
                    3 => 2100.0,
                    4 => 6000.0,
                    5 => 50000.0,
                    default => 240.0,
                };

                $payoutKobo = $isWinner ? (int) round($lockedTicket->stake_kobo * $multiplier) : 0;
                $status = $isWinner ? 'WON' : 'LOST';

                $lockedTicket->update([
                    'lifecycle_status' => $status,
                    'payout_kobo' => $payoutKobo,
                    'payout_amount' => round($payoutKobo / 100, 2),
                    'status' => $isWinner ? 'won' : 'settled',
                ]);

                if ($isWinner && $payoutKobo > 0) {
                    $player = Player::where('id', $lockedTicket->player_id)->lockForUpdate()->first();
                    $player->cash_balance_kobo += $payoutKobo;
                    $player->save();

                    // Notify winning player via WhatsApp
                    try {
                        $payoutNgn = number_format($payoutKobo / 100, 2);
                        $winMsg = "🎉 *CONGRATULATIONS! YOU WON!* 🏆\n"
                                . "━━━━━━━━━━━━━━━━━━━━\n"
                                . "• Draw: *{$draw->draw_code}*\n"
                                . "• Ticket: *{$lockedTicket->ticket_reference}*\n"
                                . "• Winning Numbers: *[ " . implode(' - ', $winningNumbers) . " ]*\n"
                                . "• Your Payout: *{$tenant->currency} {$payoutNgn}*\n"
                                . "━━━━━━━━━━━━━━━━━━━━\n"
                                . "Your winnings have been credited to your cash balance! Reply *BAL* to check wallet.";

                        $gateway->sendTextMessage($player->phone, $winMsg, $tenant->whatsapp_number);
                    } catch (\Throwable $e) {
                        Log::warning("[Settlement] Win notification failed: {$e->getMessage()}");
                    }
                }
            });
        }

        $draw->update([
            'status' => GameDraw::STATUS_SETTLED,
            'settled_at' => now(),
        ]);

        Log::info("[Settlement] Draw #{$draw->draw_code} settled successfully.");
    }
}

