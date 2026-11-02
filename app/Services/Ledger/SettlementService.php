<?php

namespace App\Services\Ledger;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    /**
     * Compute the full commercial balance sheet for a tenant over a given period.
     */
    public function getBalanceSheet(Tenant $tenant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        // 1. Turnover & Tickets
        $ticketQuery = BetTicket::where('tenant_id', $tenant->id)
            ->where('status', 'accepted')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalStakes = (float)(clone $ticketQuery)->sum('stake_amount');
        $totalTickets = (clone $ticketQuery)->count();

        // 2. Winnings & Payouts
        $totalWinnings = (float)BetTicket::where('tenant_id', $tenant->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('payout_amount');

        // 3. Gross Gaming Revenue (GGR) = Stakes - Winnings
        $ggr = max(0, $totalStakes - $totalWinnings);

        // 3b. Open Draw Liability vs Settled Revenue
        $openTicketsQuery = BetTicket::where('tenant_id', $tenant->id)
            ->where('lifecycle_status', 'OPEN')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $openDrawLiabilityKobo = (int) (clone $openTicketsQuery)->sum('stake_kobo');
        $openTicketsCount = (clone $openTicketsQuery)->count();

        $settledTicketsQuery = BetTicket::where('tenant_id', $tenant->id)
            ->whereIn('lifecycle_status', ['WON', 'LOST'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        $settledRevenueKobo = (int) (clone $settledTicketsQuery)->sum('stake_kobo');
        $settledTicketsCount = (clone $settledTicketsQuery)->count();

        // 4. Split Calculation
        // Based on agreed terms: 35% Q4I Limited / 65% Operator
        $q4iShare = round(($totalStakes * $tenant->q4i_split_percent) / 100, 2);
        $operatorShare = round($totalStakes - $q4iShare, 2);

        // 5. Ledger Audit Validation
        $ledgerQ4ITotal = (float)LedgerEntry::where('tenant_id', $tenant->id)
            ->where('entry_type', 'Q4I_COMMISSION')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $ledgerOperatorTotal = (float)LedgerEntry::where('tenant_id', $tenant->id)
            ->where('entry_type', 'OPERATOR_POOL')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Check if cryptographic ledger reconciles 100% with ticket sums
        $isReconciled = (abs($q4iShare - $ledgerQ4ITotal) < 0.05);

        // 6. Settlements & Available to Spend
        // Total settled withdrawals paid out to Q4I
        $totalSettledQ4I = (float)LedgerEntry::where('tenant_id', $tenant->id)
            ->where('entry_type', 'SETTLEMENT_WITHDRAWAL')
            ->sum('amount');

        $availableToSpendQ4I = max(0, $q4iShare - $totalSettledQ4I);

        return [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'currency' => $tenant->currency,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
            ],
            'financials' => [
                'total_turnover' => $totalStakes,
                'total_tickets' => $totalTickets,
                'total_winnings_paid' => $totalWinnings,
                'gross_gaming_revenue' => $ggr,
                'open_draw_liability' => round($openDrawLiabilityKobo / 100, 2),
                'open_tickets_count' => $openTicketsCount,
                'settled_revenue' => round($settledRevenueKobo / 100, 2),
                'settled_tickets_count' => $settledTicketsCount,
            ],
            'splits' => [
                'q4i_percentage' => $tenant->q4i_split_percent,
                'q4i_accrued_earned' => $q4iShare,
                'q4i_already_withdrawn' => $totalSettledQ4I,
                'q4i_available_to_spend' => $availableToSpendQ4I,

                'operator_percentage' => $tenant->operator_split_percent,
                'operator_accrued_pool' => $operatorShare,
            ],
            'audit' => [
                'ledger_reconciled' => $isReconciled,
                'ledger_q4i_total' => $ledgerQ4ITotal,
                'ledger_operator_total' => $ledgerOperatorTotal,
            ],
        ];
    }
}

