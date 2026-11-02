<?php

namespace App\Console\Commands;

use App\Models\GameDraw;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseExpiredDrawsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'draws:close-expired';

    /**
     * The console command description.
     */
    protected $description = 'Automatically lock draws that reached cut-off (closes_at - 60s) to prevent late bets.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Draw cut-off: 60 seconds before official draw time to lock out late bets
        $cutoffTime = now()->addSeconds(60);

        $drawsToLock = GameDraw::where('status', GameDraw::STATUS_OPEN)
            ->where('closes_at', '<=', $cutoffTime)
            ->get();

        $count = 0;
        foreach ($drawsToLock as $draw) {
            $draw->update([
                'status' => GameDraw::STATUS_LOCKED,
            ]);
            $count++;

            Log::info("[DrawEngine] Locked draw #{$draw->id} ({$draw->draw_code}) at cutoff threshold.");
        }

        $this->info("Successfully locked {$count} expired or cutoff draw(s).");

        return Command::SUCCESS;
    }
}

