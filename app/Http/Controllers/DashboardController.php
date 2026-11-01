<?php

namespace App\Http\Controllers;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Ledger\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();

        // If user is an operator rep, restrict strictly to their assigned tenant
        if ($user && $user->isOperatorRep() && $user->tenant_id) {
            $tenant = Tenant::findOrFail($user->tenant_id);
            $allTenants = collect([$tenant]);
        } else {
            $tenantId = $request->query('tenant_id');
            $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();
            $allTenants = Tenant::where('is_active', true)->get();
        }

        if (!$tenant) {
            abort(404, 'No active tenant found.');
        }

        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : now()->startOfMonth();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date')) : now()->endOfMonth();

        $balanceSheet = $this->settlementService->getBalanceSheet($tenant, $startDate, $endDate);

        // Recent Tickets
        $recentTickets = BetTicket::where('tenant_id', $tenant->id)
            ->with('player')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Recent Cryptographic Ledger
        $recentLedger = LedgerEntry::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // Registered Players (Newcomers section)
        $playersQuery = Player::where('tenant_id', $tenant->id)->orderByDesc('id');
        $totalPlayers = (clone $playersQuery)->count();
        $newPlayersToday = (clone $playersQuery)->whereDate('created_at', today())->count();
        $recentPlayers = (clone $playersQuery)->withCount('betTickets')->limit(15)->get();

        // Active Reps for this tenant
        $tenantReps = User::where('tenant_id', $tenant->id)->get();

        $currentTab = $request->query('tab', 'overview');

        return view('dashboard.index', compact(
            'tenant',
            'allTenants',
            'balanceSheet',
            'recentTickets',
            'recentLedger',
            'startDate',
            'endDate',
            'totalPlayers',
            'newPlayersToday',
            'recentPlayers',
            'tenantReps',
            'user',
            'currentTab'
        ));
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
