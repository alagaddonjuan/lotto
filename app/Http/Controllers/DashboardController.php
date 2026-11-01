<?php

namespace App\Http\Controllers;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use App\Services\Ledger\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    public function index(Request $request): View
    {
        $tenantId = $request->query('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

        if (!$tenant) {
            abort(404, 'No active tenant found.');
        }

        $allTenants = Tenant::where('is_active', true)->get();

        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : now()->startOfMonth();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date')) : now()->endOfMonth();

        $balanceSheet = $this->settlementService->getBalanceSheet($tenant, $startDate, $endDate);

        $recentTickets = BetTicket::where('tenant_id', $tenant->id)
            ->with('player')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $recentLedger = LedgerEntry::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('dashboard.index', compact('tenant', 'allTenants', 'balanceSheet', 'recentTickets', 'recentLedger', 'startDate', 'endDate'));
    }

    public function stats(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $sheet = $this->settlementService->getBalanceSheet($tenant);

        return response()->json($sheet);
    }
}

