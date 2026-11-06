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

        // Player Language Preference Analytics
        $languageStats = Player::where('tenant_id', $tenant->id)
            ->selectRaw("COALESCE(NULLIF(language, ''), 'en') as lang, count(*) as count")
            ->groupBy('lang')
            ->pluck('count', 'lang')
            ->toArray();

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
            'languageStats',
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
            'whatsapp_driver' => 'meta',
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

    /**
     * Update Meta Cloud API WhatsApp Gateway credentials for a tenant directly from the Dashboard UI.
     */
    public function updateMetaSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'whatsapp_number' => ['required', 'string', 'max:30'],
            'phone_number_id' => ['nullable', 'string', 'max:64'],
            'access_token' => ['nullable', 'string', 'max:1024'],
            'app_secret' => ['nullable', 'string', 'max:255'],
            'verify_token' => ['nullable', 'string', 'max:128'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $config = is_array($tenant->driver_config) ? $tenant->driver_config : [];

        if (!empty($validated['phone_number_id'])) {
            $config['phone_number_id'] = trim($validated['phone_number_id']);
        }
        if (!empty($validated['access_token'])) {
            $config['access_token'] = trim($validated['access_token']);
        }
        if (!empty($validated['app_secret'])) {
            $config['app_secret'] = trim($validated['app_secret']);
        }
        if (!empty($validated['verify_token'])) {
            $config['verify_token'] = trim($validated['verify_token']);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $validated['whatsapp_number']);

        $tenant->update([
            'whatsapp_number' => $cleanPhone ?: $validated['whatsapp_number'],
            'whatsapp_driver' => 'meta',
            'driver_config' => $config,
        ]);

        return redirect()->route('dashboard', ['tenant_id' => $tenant->id, 'tab' => 'meta'])
            ->with('success', "Meta Cloud API WhatsApp credentials saved for {$tenant->name}!");
    }

    /**
     * Dispatch a live test WhatsApp message via Meta Cloud API to verify connectivity.
     */
    public function sendTestWhatsAppMessage(Request $request, \App\Services\WhatsApp\WhatsAppManager $whatsAppManager): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'recipient_phone' => ['required', 'string', 'min:10', 'max:20'],
            'test_message' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $recipient = preg_replace('/[^0-9]/', '', $validated['recipient_phone']);
        if (str_starts_with($recipient, '0')) {
            $recipient = '234' . substr($recipient, 1);
        }

        $body = ($validated['test_message'] ?? null)
            ?: "✅ *Q4I Clearinghouse Live Test*\nMeta Cloud API v20.0 connection for *{$tenant->name}* is active! Reply *HI* or *PLAY* to test the conversational bot.";

        $driver = $whatsAppManager->driver($tenant);
        $sent = $driver->sendTextMessage($recipient, $body, $tenant->whatsapp_number);

        if ($sent) {
            return redirect()->route('dashboard', ['tenant_id' => $tenant->id, 'tab' => 'meta'])
                ->with('success', "Test WhatsApp message dispatched to +{$recipient}! Check your WhatsApp (or storage/logs/laravel.log if token is not yet live).");
        }

        return redirect()->route('dashboard', ['tenant_id' => $tenant->id, 'tab' => 'meta'])
            ->withErrors(['meta' => "Meta Graph API rejected the test message to +{$recipient}. Verify your Phone Number ID, Access Token, and that +{$recipient} is added as a test recipient in Meta Developer Console."]);
    }
}
