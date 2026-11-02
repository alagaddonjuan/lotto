<?php

namespace App\Http\Controllers;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\LottoGame;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\SettlementService;
use App\Services\Operators\OperatorManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService,
        protected LedgerService $ledgerService,
        protected OperatorManager $operatorManager
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

        // Recent Tickets with players
        $recentTickets = BetTicket::where('tenant_id', $tenant->id)
            ->with('player')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        // Recent Cryptographic Ledger
        $recentLedger = LedgerEntry::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Registered Players (Newcomers section)
        $playersQuery = Player::where('tenant_id', $tenant->id)->orderByDesc('id');
        $totalPlayers = (clone $playersQuery)->count();
        $newPlayersToday = (clone $playersQuery)->whereDate('created_at', today())->count();
        $recentPlayers = (clone $playersQuery)->withCount('betTickets')->limit(20)->get();

        // Active Reps for this tenant
        $tenantReps = User::where('tenant_id', $tenant->id)->get();

        // Active Lotto Games for this operator
        $games = LottoGame::where('tenant_id', $tenant->id)->get();

        // Active Draws & Operations Monitor
        $activeDraws = \App\Models\GameDraw::where('tenant_id', $tenant->id)
            ->with(['lottoGame', 'enteredBy', 'approvedBy'])
            ->whereIn('status', [\App\Models\GameDraw::STATUS_OPEN, \App\Models\GameDraw::STATUS_LOCKED, \App\Models\GameDraw::STATUS_RESULT_PENDING])
            ->orderBy('closes_at')
            ->get();

        $pendingMakerChecker = \App\Models\GameDraw::where('tenant_id', $tenant->id)
            ->where('status', \App\Models\GameDraw::STATUS_RESULT_PENDING)
            ->with(['lottoGame', 'enteredBy'])
            ->get();

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
            'games',
            'activeDraws',
            'pendingMakerChecker',
            'user',
            'currentTab'
        ));
    }

    /**
     * Return live balance sheet stats as JSON for auto-refreshing dashboard widgets.
     */
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

    /**
     * Simulate placing a real WhatsApp bet directly from the dashboard.
     */
    public function simulateBet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'phone' => ['required', 'string'],
            'game_code' => ['required', 'string'],
            'stake' => ['required', 'numeric', 'min:50', 'max:50000'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);

        $player = Player::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $phone],
            [
                'name' => 'Demo Player ' . substr($phone, -4),
                'pin_hash' => bcrypt('1234'),
                'kyc_status' => 'BASIC',
                'last_active_at' => now(),
            ]
        );
        $player->update(['last_active_at' => now()]);

        $ticketRef = 'ML-DASH-' . strtoupper(Str::random(8));
        $selectedNumbers = [rand(1, 15), rand(16, 30), rand(31, 45), rand(46, 60), rand(61, 75)];

        // Compute 35/65 split
        $stake = (float)$validated['stake'];
        $q4iShare = round(($stake * $tenant->q4i_split_percent) / 100, 2);
        $operatorShare = round($stake - $q4iShare, 2);

        // Record ticket
        $ticket = BetTicket::create([
            'tenant_id' => $tenant->id,
            'player_id' => $player->id,
            'ticket_reference' => $ticketRef,
            'idempotency_key' => 'SIM_IDEMP_' . Str::random(24),
            'game_code' => $validated['game_code'],
            'selected_numbers' => $selectedNumbers,
            'stake_amount' => $stake,
            'currency' => $tenant->currency,
            'q4i_share' => $q4iShare,
            'operator_share' => $operatorShare,
            'status' => 'accepted',
            'placed_via' => 'dashboard_sim',
        ]);

        // Cryptographic HMAC-SHA256 Ledger
        $this->ledgerService->recordBetPlacement($ticket);

        return response()->json([
            'success' => true,
            'message' => "Simulated ticket {$ticketRef} created successfully with 35% / 65% split!",
            'ticket' => [
                'reference' => $ticket->ticket_reference,
                'stake' => $ticket->stake_amount,
                'q4i_share' => $ticket->q4i_share,
                'operator_share' => $ticket->operator_share,
                'hash' => $ticket->hash,
            ],
        ]);
    }

    /**
     * Simulate a Dedicated Virtual Account (DVA) bank deposit.
     */
    public function simulateDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:500', 'max:500000'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);

        $player = Player::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $phone],
            ['name' => 'Player ' . substr($phone, -4), 'pin_hash' => bcrypt('1234')]
        );

        $operator = $this->operatorManager->driver($tenant);
        $ref = 'DASH_DEP_' . strtoupper(Str::random(8));
        $operator->creditWallet($phone, (float)$validated['amount'], $ref);
        $wallet = $operator->checkWalletBalance($phone);

        return response()->json([
            'success' => true,
            'message' => "₦" . number_format($validated['amount'], 2) . " credited to +{$phone} successfully!",
            'balance' => $wallet['balance'],
        ]);
    }

    /**
     * Toggle Two-Factor Authentication (2FA) for current authenticated user.
     */
    public function toggle2FA(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->two_factor_enabled = !$user->two_factor_enabled;
        if ($user->two_factor_enabled) {
            $user->generateTwoFactorCode();
        } else {
            $user->resetTwoFactorCode();
        }
        $user->save();

        return response()->json([
            'success' => true,
            'two_factor_enabled' => $user->two_factor_enabled,
            'message' => $user->two_factor_enabled
                ? 'Two-Factor Authentication is now ENABLED for your account.'
                : 'Two-Factor Authentication has been disabled.',
        ]);
    }

    /**
     * Onboard a new operator tenant (Super Admin only).
     */
    public function createTenant(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admins can onboard new operators.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3'],
            'whatsapp_number' => ['required', 'string', 'max:30'],
            'q4i_split_percent' => ['required', 'numeric', 'min:5', 'max:50'],
        ]);

        $slug = Str::slug($validated['name']);
        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'country_code' => strtoupper($validated['country_code']),
            'currency' => strtoupper($validated['currency']),
            'whatsapp_number' => $validated['whatsapp_number'],
            'whatsapp_driver' => 'twilio',
            'operator_driver' => 'modern_lottery',
            'q4i_split_percent' => $validated['q4i_split_percent'],
            'operator_split_percent' => 100 - $validated['q4i_split_percent'],
            'is_active' => true,
        ]);

        return back()->with('success', "Operator {$tenant->name} onboarded successfully!");
    }

    /**
     * Invite/Add a new operator representative for this tenant.
     */
    public function inviteRep(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'tenant_id' => $validated['tenant_id'],
            'role' => 'operator_rep',
            'password' => Hash::make($validated['password']),
            'two_factor_enabled' => true,
        ]);

        return back()->with('success', "Operator representative {$validated['email']} created with 2FA enabled!");
    }
}
