<?php

namespace App\Services\Ledger;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Record a validated bet ticket into the immutable double-entry ledger.
     */
    public function recordBetPlacement(BetTicket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $tenant = $ticket->tenant;

            // 1. Calculate splits
            $q4iShare = round(($ticket->stake_amount * $tenant->q4i_split_percent) / 100, 2);
            $operatorShare = round($ticket->stake_amount - $q4iShare, 2);

            $ticket->update([
                'q4i_share' => $q4iShare,
                'operator_share' => $operatorShare,
            ]);

            // 2. Fetch the previous hash in the tenant's chain
            $latestEntry = LedgerEntry::where('tenant_id', $tenant->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $prevHash = $latestEntry?->hash ?? str_repeat('0', 64);

            // 3. Create entry for Q4I Commission (35%)
            $q4iHash = hash('sha256', implode('|', [
                $tenant->id,
                $ticket->id,
                'Q4I_COMMISSION',
                $q4iShare,
                $tenant->currency,
                $prevHash,
                now()->toIso8601String(),
            ]));

            $q4iEntry = LedgerEntry::create([
                'tenant_id' => $tenant->id,
                'bet_ticket_id' => $ticket->id,
                'entry_type' => 'Q4I_COMMISSION',
                'debit_account' => 'PLAYER_STAKE_ESCROW',
                'credit_account' => 'Q4I_EARNED_REVENUE',
                'amount' => $q4iShare,
                'currency' => $tenant->currency,
                'previous_hash' => $prevHash,
                'hash' => $q4iHash,
                'metadata' => [
                    'split_percent' => $tenant->q4i_split_percent,
                    'ticket_ref' => $ticket->ticket_reference,
                ],
                'created_at' => now(),
            ]);

            // 4. Create entry for Operator Pool (65%) chained to Q4I entry
            $operatorHash = hash('sha256', implode('|', [
                $tenant->id,
                $ticket->id,
                'OPERATOR_POOL',
                $operatorShare,
                $tenant->currency,
                $q4iHash,
                now()->toIso8601String(),
            ]));

            LedgerEntry::create([
                'tenant_id' => $tenant->id,
                'bet_ticket_id' => $ticket->id,
                'entry_type' => 'OPERATOR_POOL',
                'debit_account' => 'PLAYER_STAKE_ESCROW',
                'credit_account' => 'OPERATOR_GAME_POOL',
                'amount' => $operatorShare,
                'currency' => $tenant->currency,
                'previous_hash' => $q4iHash,
                'hash' => $operatorHash,
                'metadata' => [
                    'split_percent' => $tenant->operator_split_percent,
                    'ticket_ref' => $ticket->ticket_reference,
                ],
                'created_at' => now(),
            ]);

            // 5. Seal the ticket with the cryptographic hash
            $ticket->update(['hash' => $operatorHash]);
        });
    }

    /**
     * Compute real-time revenue and split summary for a tenant.
     */
    public function getTenantRevenueSummary(Tenant $tenant, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = LedgerEntry::where('tenant_id', $tenant->id);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalQ4IRevenue = (clone $query)
            ->where('entry_type', 'Q4I_COMMISSION')
            ->sum('amount');

        $totalOperatorPool = (clone $query)
            ->where('entry_type', 'OPERATOR_POOL')
            ->sum('amount');

        $totalTurnover = $totalQ4IRevenue + $totalOperatorPool;

        return [
            'currency' => $tenant->currency,
            'total_turnover' => (float)$totalTurnover,
            'q4i_revenue' => (float)$totalQ4IRevenue,
            'operator_revenue' => (float)$totalOperatorPool,
            'q4i_percentage' => $tenant->q4i_split_percent,
            'operator_percentage' => $tenant->operator_split_percent,
        ];
    }
}

