<?php

namespace App\Http\Controllers;

use App\DTOs\IncomingMessageDTO;
use App\Models\BetTicket;
use App\Models\Player;
use App\Models\Tenant;
use App\Services\Bot\LottoConversationEngine;
use App\Services\Ledger\LedgerService;
use App\Services\Operators\OperatorManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlayerPortalController extends Controller
{
    public function __construct(
        protected LottoConversationEngine $botEngine,
        protected LedgerService $ledgerService,
        protected OperatorManager $operatorManager
    ) {}

    /**
     * Display the Player Web Kiosk & Registration Simulator.
     */
    public function index(Request $request): View
    {
        $tenant = Tenant::first() ?? Tenant::create([
            'name' => 'Modern Lottery Limited',
            'slug' => 'modern-lottery-ng',
            'country_code' => 'NG',
            'currency' => 'NGN',
            'q4i_share_pct' => 35.00,
            'operator_share_pct' => 65.00,
            'whatsapp_number' => '2348000000000',
            'is_active' => true,
        ]);

        $defaultPhone = $request->query('phone', '2348039876543');
        $player = Player::where('tenant_id', $tenant->id)->where('phone', $defaultPhone)->first();

        $recentTickets = $player
            ? BetTicket::where('player_id', $player->id)->latest()->take(10)->get()
            : collect();

        $operator = $this->operatorManager->driver($tenant);
        $walletBal = $player ? $operator->checkWalletBalance($player->phone)['balance'] : 0.00;

        return view('portal.play', compact('tenant', 'player', 'recentTickets', 'walletBal', 'defaultPhone'));
    }

    /**
     * Register a new player with welcome bonus.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:16'],
            'pin' => ['required', 'string', 'digits:4'],
        ]);

        $tenant = Tenant::first();
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '234' . substr($phone, 1);
        }

        $player = Player::updateOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $phone],
            [
                'name' => $validated['name'],
                'pin_hash' => bcrypt($validated['pin']),
                'kyc_status' => 'BASIC',
                'last_active_at' => now(),
            ]
        );

        // Reset session state
        Cache::forget("session:{$tenant->id}:{$phone}");

        $operator = $this->operatorManager->driver($tenant);
        $wallet = $operator->checkWalletBalance($phone);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully with ₦4,500.00 trial credit!',
            'player' => [
                'name' => $player->name,
                'phone' => $player->phone,
                'balance' => $wallet['balance'],
            ],
        ]);
    }

    /**
     * Process a simulated conversational chat message.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ]);

        $tenant = Tenant::first();
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);

        // We capture outgoing messages sent to this phone during processing
        $capturedReplies = [];
        Cache::forget("sim_reply:{$phone}");

        $dto = new IncomingMessageDTO(
            from: $phone,
            to: $tenant->whatsapp_number ?? '2348000000000',
            rawBody: $validated['message'],
            messageId: 'SIM_' . Str::random(12),
            driver: 'simulator',
            rawPayload: ['simulator' => true]
        );

        // Listen for outgoing messages sent via driver or intercept
        $this->botEngine->handle($tenant, $dto);

        // Retrieve player & updated balance
        $player = Player::where('tenant_id', $tenant->id)->where('phone', $phone)->first();
        $operator = $this->operatorManager->driver($tenant);
        $wallet = $player ? $operator->checkWalletBalance($phone) : ['balance' => 0];

        $latestTickets = $player
            ? BetTicket::where('player_id', $player->id)->latest()->take(5)->get()
            : collect();

        // Check if there was a cached reply stored
        $reply = Cache::get("sim_reply:{$phone}", null);
        if (!$reply) {
            // Provide intelligent fallback from session state
            $session = Cache::get("session:{$tenant->id}:{$phone}", ['state' => 'USSD_MENU']);
            $reply = "✅ Message received and processed for {$phone}. (Current State: {$session['state']})";
        }

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'balance' => $wallet['balance'],
            'recentTickets' => $latestTickets,
        ]);
    }

    /**
     * Fund player wallet instantly.
     */
    public function fund(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:100', 'max:500000'],
        ]);

        $tenant = Tenant::first();
        $phone = preg_replace('/[^0-9]/', '', $validated['phone']);

        $player = Player::where('tenant_id', $tenant->id)->where('phone', $phone)->first();
        if (! $player) {
            $player = Player::create([
                'tenant_id' => $tenant->id,
                'phone' => $phone,
                'name' => 'Player ' . substr($phone, -4),
                'pin_hash' => bcrypt('1234'),
                'last_active_at' => now(),
            ]);
        }

        // Add ledger entry and credit wallet
        $operator = $this->operatorManager->driver($tenant);
        $operator->creditWallet($phone, (float) $validated['amount'], 'WEB_SIM_DEPOSIT_' . Str::random(8));
        $wallet = $operator->checkWalletBalance($phone);

        return response()->json([
            'success' => true,
            'message' => "₦" . number_format($validated['amount'], 2) . " credited to wallet successfully!",
            'balance' => $wallet['balance'],
        ]);
    }
}
