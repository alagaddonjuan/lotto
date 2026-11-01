<?php

namespace App\Http\Controllers;

use App\Models\BetTicket;
use App\Models\Tenant;
use App\Services\Ledger\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementStatementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    /**
     * Download Reconciliation Statement as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenantId = $request->query('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        $filename = 'q4i-reconciliation-' . $tenant->slug . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($tenant) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Ticket Reference',
                'Timestamp (UTC)',
                'Player Phone',
                'Game Code',
                'Stake (' . $tenant->currency . ')',
                'Q4I Commission (35%)',
                'Operator Share (65%)',
                'Status',
                'SHA-256 Cryptographic Hash',
            ]);

            BetTicket::where('tenant_id', $tenant->id)
                ->with('player')
                ->latest()
                ->chunk(200, function ($tickets) use ($handle) {
                    foreach ($tickets as $t) {
                        fputcsv($handle, [
                            $t->ticket_reference,
                            $t->created_at->toIso8601String(),
                            $t->player ? '+' . $t->player->phone : 'N/A',
                            $t->game_code,
                            number_format($t->stake_amount, 2, '.', ''),
                            number_format($t->q4i_share, 2, '.', ''),
                            number_format($t->operator_share, 2, '.', ''),
                            $t->status,
                            $t->hash,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Display or Print Official Bank-Grade Settlement Statement / Invoice.
     */
    public function printInvoice(Request $request): View
    {
        $tenantId = $request->query('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        $balanceSheet = $this->settlementService->getBalanceSheet($tenant);
        $recentTickets = BetTicket::where('tenant_id', $tenant->id)->latest()->take(25)->get();

        return view('dashboard.settlement-invoice', compact('tenant', 'balanceSheet', 'recentTickets'));
    }
}

