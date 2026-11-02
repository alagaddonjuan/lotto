<?php

namespace Tests\Feature;

use App\Console\Commands\CloseExpiredDrawsCommand;
use App\Http\Controllers\Dashboard\DrawManagementController;
use App\Http\Controllers\PaymentWebhookController;
use App\Jobs\AppendCryptographicLedgerJob;
use App\Models\BetTicket;
use App\Models\GameDraw;
use App\Models\LedgerEntry;
use App\Models\LottoGame;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Betting\PlaceBetService;
use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\UserSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InstitutionalClearinghouseTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected LottoGame $game;
    protected PlaceBetService $betService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ModernLotterySeeder::class);
        $this->seed(UserSeeder::class);

        $this->tenant = Tenant::first();
        $this->game = LottoGame::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'game_code' => '5_90_DAILY'],
            [
                'name' => 'Modern 5/90 Direct',
                'min_stake' => 100,
                'max_stake' => 50000,
                'total_pool_numbers' => 90,
                'required_picks' => 5,
                'is_active' => true,
            ]
        );

        $this->betService = app(PlaceBetService::class);
    }

    /**
     * TEST 1: Dual-Wallet Isolation & Integer Kobo Math
     */
    public function test_dual_wallet_kobo_math_cash_splits_35_65_while_bonus_does_not(): void
    {
        // 1. Setup GameDraw
        $draw = GameDraw::create([
            'tenant_id' => $this->tenant->id,
            'lotto_game_id' => $this->game->id,
            'draw_code' => 'TEST-DRAW-001',
            'status' => GameDraw::STATUS_OPEN,
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHours(2),
        ]);

        // 2. Setup Player with 10,000 NGN Cash (1,000,000 Kobo) and 2,000 NGN Bonus (200,000 Kobo)
        $player = Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => '2348011112222',
            'name' => 'Institutional Player',
            'cash_balance_kobo' => 1000000,
            'bonus_balance_kobo' => 200000,
            'last_active_at' => now(),
        ]);

        // --- PART A: CASH STAKE (₦1,000.00 = 100,000 Kobo) ---
        $stakeKobo = 100000;
        $cashTicket = $this->betService->placeBet(
            player: $player,
            gameDraw: $draw,
            selectedNumbers: [5, 12, 28, 34, 45],
            stakeKobo: $stakeKobo,
            walletSource: 'CASH'
        );

        $player->refresh();
        $this->assertEquals(900000, $player->cash_balance_kobo, 'Cash balance was debited exactly 100,000 Kobo');
        $this->assertEquals(200000, $player->bonus_balance_kobo, 'Bonus balance was not touched');

        // Check 35% Q4I / 65% Operator Split
        $expectedQ4i = (int) round($stakeKobo * 0.35); // 35,000 Kobo
        $expectedOperator = $stakeKobo - $expectedQ4i;  // 65,000 Kobo

        $this->assertEquals($expectedQ4i, $cashTicket->q4i_commission_kobo);
        $this->assertEquals($expectedOperator, $cashTicket->operator_pool_kobo);
        $this->assertEquals('CASH', $cashTicket->wallet_source);

        // Check Cryptographic Ledger Entry
        $q4iEntry = LedgerEntry::where('bet_ticket_id', $cashTicket->id)
            ->where('entry_type', 'Q4I_COMMISSION')
            ->first();
        $this->assertNotNull($q4iEntry, 'Q4I Commission entry created in ledger');
        $this->assertEquals(350.00, $q4iEntry->amount);
        $this->assertNotEmpty($cashTicket->fresh()->hash, 'Ticket sealed with SHA-256 HMAC hash');

        // --- PART B: BONUS STAKE (₦500.00 = 50,000 Kobo) ---
        $bonusTicket = $this->betService->placeBet(
            player: $player,
            gameDraw: $draw,
            selectedNumbers: [7, 14, 21, 35, 42],
            stakeKobo: 50000,
            walletSource: 'BONUS'
        );

        $player->refresh();
        $this->assertEquals(900000, $player->cash_balance_kobo, 'Cash balance untouched by bonus stake');
        $this->assertEquals(150000, $player->bonus_balance_kobo, 'Bonus balance debited 50,000 Kobo');

        // Bonus stakes must NEVER split into executive clearinghouse ledger
        $this->assertEquals(0, $bonusTicket->q4i_commission_kobo);
        $this->assertEquals(0, $bonusTicket->operator_pool_kobo);

        $bonusLedgerEntry = LedgerEntry::where('bet_ticket_id', $bonusTicket->id)->first();
        $this->assertNull($bonusLedgerEntry, 'Bonus ticket generated 0 commercial ledger entries');
    }

    /**
     * TEST 2: Pessimistic Concurrency & Balance Protection
     */
    public function test_insufficient_balance_throws_domain_exception_without_corrupting_wallet(): void
    {
        $draw = GameDraw::create([
            'tenant_id' => $this->tenant->id,
            'lotto_game_id' => $this->game->id,
            'draw_code' => 'TEST-DRAW-002',
            'status' => GameDraw::STATUS_OPEN,
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHours(2),
        ]);

        $player = Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => '2348022223333',
            'cash_balance_kobo' => 50000, // 500 NGN
            'bonus_balance_kobo' => 0,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Insufficient cash balance');

        // Attempt to stake 1,000 NGN (100,000 Kobo) when player only has 500 NGN
        $this->betService->placeBet(
            player: $player,
            gameDraw: $draw,
            selectedNumbers: [1, 2, 3, 4, 5],
            stakeKobo: 100000,
            walletSource: 'CASH'
        );

        $player->refresh();
        $this->assertEquals(50000, $player->cash_balance_kobo, 'Wallet was not altered by failed bet');
    }

    /**
     * TEST 3: Draw Cut-Off Scheduler and Late Bet Rejection
     */
    public function test_draw_cutoff_scheduler_locks_draw_and_rejects_late_bets(): void
    {
        // Draw that closes in 30 seconds (within the 60s cutoff threshold)
        $closingSoonDraw = GameDraw::create([
            'tenant_id' => $this->tenant->id,
            'lotto_game_id' => $this->game->id,
            'draw_code' => 'CUTOFF-DRAW-001',
            'status' => GameDraw::STATUS_OPEN,
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addSeconds(30),
        ]);

        // Run automated cut-off scheduler
        Artisan::call('draws:close-expired');

        $closingSoonDraw->refresh();
        $this->assertEquals(GameDraw::STATUS_LOCKED, $closingSoonDraw->status, 'Draw within 60s of closes_at was transitioned to LOCKED');

        // Late bet injection attempt must be blocked
        $player = Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => '2348033334444',
            'cash_balance_kobo' => 500000,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("is currently LOCKED");

        $this->betService->placeBet(
            player: $player,
            gameDraw: $closingSoonDraw,
            selectedNumbers: [10, 20, 30, 40, 50],
            stakeKobo: 10000,
            walletSource: 'CASH'
        );
    }

    /**
     * TEST 4: Maker-Checker Segregation of Duties
     */
    public function test_maker_checker_strictly_prevents_same_user_from_approving_own_results(): void
    {
        $draw = GameDraw::create([
            'tenant_id' => $this->tenant->id,
            'lotto_game_id' => $this->game->id,
            'draw_code' => 'MAKER-CHECKER-001',
            'status' => GameDraw::STATUS_LOCKED,
            'opens_at' => now()->subHours(2),
            'closes_at' => now()->subMinute(),
        ]);

        $makerUser = User::create([
            'name' => 'Officer Maker',
            'email' => 'maker@q4iltd.com',
            'password' => Hash::make('password123'),
            'role' => 'operator_rep',
            'tenant_id' => $this->tenant->id,
            'two_factor_enabled' => true,
        ]);

        $checkerUser = User::create([
            'name' => 'Supervisor Checker',
            'email' => 'checker@q4iltd.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'tenant_id' => $this->tenant->id,
            'two_factor_enabled' => true,
        ]);

        $controller = app(DrawManagementController::class);

        // 1. Maker enters winning numbers
        Auth::login($makerUser);
        $enterRequest = Request::create('/dashboard/draws/' . $draw->id . '/enter-results', 'POST', [
            'winning_numbers' => [7, 14, 21, 35, 42],
        ]);
        $controller->enterResults($enterRequest, $draw);

        $draw->refresh();
        $this->assertEquals(GameDraw::STATUS_RESULT_PENDING, $draw->status);
        $this->assertEquals($makerUser->id, $draw->result_entered_by);
        $this->assertEquals([7, 14, 21, 35, 42], $draw->winning_numbers);

        // 2. Maker attempts to approve own results -> MUST THROW 403 HTTP EXCEPTION
        $approveRequest = Request::create('/dashboard/draws/' . $draw->id . '/approve-results', 'POST');
        
        $caught403 = false;
        try {
            $controller->approveResults($approveRequest, $draw);
        } catch (HttpException $e) {
            $caught403 = ($e->getStatusCode() === 403);
        }

        $this->assertTrue($caught403, 'Maker-Checker policy strictly aborted self-approval with HTTP 403');

        // 3. Independent Supervisor (Checker) approves results -> SUCCEEDS
        Auth::login($checkerUser);
        $controller->approveResults($approveRequest, $draw);

        $draw->refresh();
        $this->assertEquals($checkerUser->id, $draw->result_approved_by);
        $this->assertEquals(GameDraw::STATUS_SETTLED, $draw->status, 'Draw is settled after checker approval');
    }

    /**
     * TEST 5: Webhook Idempotency (Database Constraint Enforcement)
     */
    public function test_payment_webhook_idempotency_prevents_duplicate_credits(): void
    {
        $player = Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => '2348055556666',
            'cash_balance_kobo' => 0,
            'bonus_balance_kobo' => 0,
        ]);

        $webhookController = app(PaymentWebhookController::class);
        $reference = 'PAYSTACK_REF_' . uniqid();

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'amount' => 500000, // 5,000 NGN in Kobo
                'customer' => [
                    'phone' => '08055556666',
                ],
            ],
        ];

        // First delivery: processes and credits 5,000 NGN
        $req1 = Request::create('/webhook/payment', 'POST', $payload);
        $req1->headers->set('X-Payment-Provider', 'paystack');
        $res1 = $webhookController->handle($req1);
        $this->assertEquals(200, $res1->getStatusCode());

        $player->refresh();
        $this->assertEquals(500000, $player->cash_balance_kobo, 'Player credited 500,000 Kobo');

        // Second delivery (duplicate retry with same reference): must be idempotently ignored
        $req2 = Request::create('/webhook/payment', 'POST', $payload);
        $req2->headers->set('X-Payment-Provider', 'paystack');
        $res2 = $webhookController->handle($req2);
        $this->assertEquals(200, $res2->getStatusCode());

        $player->refresh();
        $this->assertEquals(500000, $player->cash_balance_kobo, 'Balance remained 500,000 Kobo without duplicate credit');

        // Verify only 1 webhook event record was created
        $eventCount = WebhookEvent::where('provider', 'paystack')
            ->where('external_reference', $reference)
            ->count();
        $this->assertEquals(1, $eventCount);
    }
}

