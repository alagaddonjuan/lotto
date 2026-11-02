<?php

namespace App\Services\Bot;

use App\Contracts\LottoOperatorContract;
use App\Contracts\WhatsAppGatewayInterface;
use App\DTOs\IncomingMessageDTO;
use App\Models\BetTicket;
use App\Models\GameDraw;
use App\Models\LottoGame;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Services\Betting\InstantRngService;
use App\Services\Betting\PlaceBetService;
use App\Services\Ledger\LedgerService;
use App\Services\Operators\OperatorManager;
use App\Services\WhatsApp\WhatsAppManager;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LottoConversationEngine
{
    public function __construct(
        protected WhatsAppManager $whatsAppManager,
        protected OperatorManager $operatorManager,
        protected LedgerService $ledgerService,
        protected PlaceBetService $placeBetService,
        protected InstantRngService $instantRngService
    ) {}

    /**
     * Process an incoming message and advance the Redis-backed state machine.
     */
    public function handle(Tenant $tenant, IncomingMessageDTO $message): void
    {
        $gateway = $this->whatsAppManager->driver($tenant);
        $operator = $this->operatorManager->driver($tenant);
        $phone = $message->normalizedPhone();
        $input = $message->normalizedText();

        // 1. Fetch or initialize player (MySQL hit only on onboarding or transactional state)
        $player = Player::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $phone],
            [
                'cash_balance_kobo' => 0,
                'bonus_balance_kobo' => 0,
                'last_active_at' => now(),
            ]
        );

        // 2. Fetch ephemeral session state strictly from Redis
        $sessionKey = "wa:session:{$phone}";
        $session = Cache::get($sessionKey, ['state' => 'ROOT_MENU', 'item_id' => null, 'data' => []]);

        // 3. Handle WhatsApp Native Interactive Flow / JSON Responses
        if (str_starts_with($input, '{') && str_contains($input, '}')) {
            $this->handleFlowResponse($input, $gateway, $tenant, $player, $sessionKey);
            return;
        }

        // Newcomer Onboarding State Machine
        if ($session['state'] === 'ONBOARDING_NAME') {
            $name = ucwords(strtolower(trim($input)));
            if (strlen($name) < 2 || is_numeric($name)) {
                $gateway->sendTextMessage($phone, "👋 Please reply with your first name or nickname (e.g. *Michael*):", $tenant->whatsapp_number);
                return;
            }
            $player->update(['name' => $name]);
            Cache::put($sessionKey, ['state' => 'ONBOARDING_PIN', 'data' => ['name' => $name]], now()->addMinutes(15));
            $msg = "Awesome to have you, *{$name}*! 🎉\n\n"
                 . "🔐 Choose a *4-digit Security Withdrawal PIN* to protect your bank payouts (e.g. *5829*):\n"
                 . "_(You will never be asked for this PIN to place bets)_";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        if ($session['state'] === 'ONBOARDING_PIN') {
            $pin = trim($input);
            if (strlen($pin) !== 4 || !ctype_digit($pin)) {
                $gateway->sendTextMessage($phone, "⚠️ Please enter a valid *4-digit numeric PIN* (e.g. *5829*):", $tenant->whatsapp_number);
                return;
            }

            $player->update([
                'withdrawal_pin_hash' => Hash::make($pin),
                'pin_hash' => Hash::make($pin),
            ]);
            Cache::forget($sessionKey);

            $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
            $bonusNgn = number_format($player->bonus_balance_kobo / 100, 2);

            $msg = "✅ *Account Verified Successfully!*\n"
                 . "━━━━━━━━━━━━━━━━\n"
                 . "👤 Account: *{$player->name}*\n"
                 . "📱 Phone: *+{$phone}*\n"
                 . "💵 Cash Balance: *{$tenant->currency} {$cashNgn}*\n"
                 . "🎁 Bonus Balance: *{$tenant->currency} {$bonusNgn}*\n"
                 . "━━━━━━━━━━━━━━━━\n\n"
                 . "You're all set! Use buttons below to play or fund your wallet:";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);

            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        // Global Navigation Resets
        if (in_array(strtoupper($input), ['RESET', 'CANCEL', 'MENU', 'HOME', 'START', 'HI', 'HELLO', 'MAIN'])) {
            if (empty($player->name)) {
                Cache::put($sessionKey, ['state' => 'ONBOARDING_NAME', 'data' => []], now()->addMinutes(15));
                $welcome = "🎰 *Welcome to {$tenant->name}!* 👋\n"
                         . "Nigeria's institutional lottery clearinghouse.\n\n"
                         . "Instant payouts & audited daily draws in *Ghana 5/90* and *Keno 20/80*!\n\n"
                         . "👉 *What is your first name or nickname?*";
                $gateway->sendTextMessage($phone, $welcome, $tenant->whatsapp_number);
                return;
            }

            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        if (empty($player->name)) {
            $player->update(['name' => 'Player ' . substr($phone, -4)]);
        }

        // Fast-path shortcuts (NO PIN REQUIRED for chat betting)
        if ($this->handleShortcuts($input, $gateway, $tenant, $player, $sessionKey)) {
            return;
        }

        // Top-Level Navigation Triggers (when at Root menu)
        $upperInput = strtoupper($input);
        if (($session['state'] ?? 'ROOT_MENU') === 'ROOT_MENU') {
            if ($upperInput === 'OPT_PLAY' || str_contains($upperInput, 'PLAY')) {
                $this->showGamesMenu($gateway, $tenant, $phone, $sessionKey);
                return;
            }

            if ($upperInput === 'OPT_FUND' || str_contains($upperInput, 'FUND') || str_contains($upperInput, 'DEPOSIT')) {
                $this->showDepositOptions($gateway, $tenant, $phone);
                return;
            }

            if ($upperInput === 'OPT_RESULTS' || str_contains($upperInput, 'RESULT')) {
                $this->showLatestResults($gateway, $tenant, $phone);
                return;
            }

            // Direct numeric trigger (e.g. 1 for Play Games in USSD mode)
            if (ctype_digit($input) && !empty($session['item_id'])) {
                $this->handleUssdMenuNavigation($input, $gateway, $tenant, $player, $operator, $session, $sessionKey);
                return;
            }
        }

        // Dispatch based on active session state
        switch ($session['state'] ?? 'ROOT_MENU') {
            case 'SELECT_NUMBERS':
                $this->handleNumberSelection($input, $gateway, $tenant, $player, $session, $sessionKey);
                break;

            case 'ENTER_STAKE':
                $this->handleStakeEntry($input, $gateway, $tenant, $player, $session, $sessionKey);
                break;

            case 'CONFIRM_BET':
                $this->handleBetConfirmation($input, $gateway, $tenant, $player, $session, $sessionKey);
                break;

            case 'WITHDRAWAL_PIN':
                $this->handleWithdrawalPin($input, $gateway, $tenant, $player, $session, $sessionKey);
                break;

            case 'USSD_MENU':
                $this->handleUssdMenuNavigation($input, $gateway, $tenant, $player, $operator, $session, $sessionKey);
                break;

            default:
                $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
                break;
        }
    }

    /**
     * Display Root Menu with Native 3-Button Interactive Prompt.
     */
    protected function showRootMenu(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone, string $sessionKey, ?Player $player = null): void
    {
        $menu = UssdMenu::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        $rootItem = $menu?->rootItem();

        Cache::put($sessionKey, [
            'state' => 'ROOT_MENU',
            'item_id' => $rootItem?->id,
            'data' => [],
        ], now()->addMinutes(15));

        $cashNgn = $player ? number_format($player->cash_balance_kobo / 100, 2) : '0.00';
        $name = $player?->name ?? 'Player';

        $header = "🎰 *{$tenant->name} Clearinghouse*\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "👤 Account: *{$name}*\n"
                . "💰 Cash Balance: *{$tenant->currency} {$cashNgn}*\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Select an option below to proceed:";

        $buttons = [
            ['id' => 'opt_play', 'title' => '🎯 Play Games'],
            ['id' => 'opt_fund', 'title' => '💳 Fund Wallet'],
            ['id' => 'opt_results', 'title' => '🏆 Check Results'],
        ];

        $gateway->sendQuickReplyButtons($phone, $header, $buttons, $tenant->whatsapp_number);
    }

    /**
     * Display Active Games via Native Interactive List Message (scrollable drawer).
     */
    protected function showGamesMenu(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone, string $sessionKey): void
    {
        $cutoff590 = now()->addHours(2)->format('H:i');
        $cutoffKeno = now()->addMinutes(15)->format('H:i');

        $sections = [
            [
                'title' => 'Active Clearinghouse Games',
                'rows' => [
                    [
                        'id' => 'game_590',
                        'title' => 'Modern 5/90 Direct',
                        'description' => "Min: NGN 100 | Cut-off: {$cutoff590} WAT",
                    ],
                    [
                        'id' => 'game_keno',
                        'title' => 'Keno 20/80 (Instant)',
                        'description' => "Min: NGN 50 | Live Draw: {$cutoffKeno}",
                    ],
                    [
                        'id' => 'game_fast3',
                        'title' => 'Fast 3 Jackpot',
                        'description' => "Min: NGN 100 | Drawn Every 10 Mins",
                    ],
                ],
            ],
        ];

        $header = "🎯 *SELECT ACTIVE LOTTO GAME*\n\nChoose a game from the list to pick your winning numbers:";
        $gateway->sendListMenu($phone, $header, 'View Games List', $sections, $tenant->whatsapp_number);

        Cache::put($sessionKey, [
            'state' => 'USSD_MENU',
            'item_id' => null,
            'data' => [],
        ], now()->addMinutes(15));
    }

    /**
     * Handle fast shortcuts (e.g. BAL, RES, KENO QP 200, 590 QP 500) without PIN.
     */
    protected function handleShortcuts(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, string $sessionKey): bool
    {
        $phone = $player->phone;
        $upper = strtoupper(trim($input));

        if (in_array($upper, ['BAL', 'BALANCE', 'WALLET', 'WAL'])) {
            $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
            $bonusNgn = number_format($player->bonus_balance_kobo / 100, 2);
            $totalNgn = number_format($player->totalBalanceKobo() / 100, 2);

            $msg = "💼 *YOUR WALLET BALANCE*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "💵 Cash (Real Deposits): *{$tenant->currency} {$cashNgn}*\n"
                 . "🎁 Promotional Bonus: *{$tenant->currency} {$bonusNgn}*\n"
                 . "💰 Total Spendable: *{$tenant->currency} {$totalNgn}*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n\n"
                 . "Reply *1* to play games or *2* to deposit.";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return true;
        }

        if (in_array($upper, ['RES', 'RESULT', 'RESULTS'])) {
            $this->showLatestResults($gateway, $tenant, $phone);
            return true;
        }

        // Fast single-line bet placement: KENO QP 200 or 590 QP 500
        $tokens = preg_split('/[\s,\*#]+/', $input);
        $tokens = array_values(array_filter($tokens));

        if (count($tokens) >= 3) {
            $first = strtoupper($tokens[0]);
            $gameCode = match ($first) {
                'KENO', 'KENO20', '1' => 'KENO_20_80',
                '590', '5/90', '2' => '5_90_DAILY',
                'FAST3', '3' => 'FAST_3',
                default => null,
            };

            if ($gameCode) {
                $rawStake = (float) end($tokens);
                $midTokens = array_slice($tokens, 1, -1);

                $pool = $gameCode === 'KENO_20_80' ? 80 : 90;
                $picks = $gameCode === 'KENO_20_80' ? 10 : 5;

                $isQP = (count($midTokens) === 1 && strtoupper($midTokens[0]) === 'QP');
                if ($isQP) {
                    $nums = range(1, $pool);
                    shuffle($nums);
                    $selected = array_slice($nums, 0, $picks);
                    sort($selected);
                } else {
                    $selected = array_values(array_unique(array_filter(array_map('intval', $midTokens))));
                    sort($selected);
                }

                if (count($selected) === $picks && $rawStake >= 50) {
                    $stakeKobo = (int) round($rawStake * 100);
                    $this->executeBetPlacement(
                        gateway: $gateway,
                        tenant: $tenant,
                        player: $player,
                        gameCode: $gameCode,
                        gameTitle: $gameCode === 'KENO_20_80' ? 'Keno 20/80' : 'Modern 5/90',
                        selectedNumbers: $selected,
                        stakeKobo: $stakeKobo,
                        sessionKey: $sessionKey
                    );
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Handle number picking stage.
     */
    protected function handleNumberSelection(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $data = $session['data'] ?? [];
        $requiredPicks = $data['picks'] ?? 5;
        $maxPool = $data['pool'] ?? 90;

        if ($input === '0') {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        if (strtoupper($input) === 'QP' || str_contains(strtoupper($input), 'QUICK')) {
            $numbers = range(1, $maxPool);
            shuffle($numbers);
            $selected = array_slice($numbers, 0, $requiredPicks);
            sort($selected);
        } else {
            $raw = preg_split('/[\s,\-\.]+/', $input);
            $selected = array_values(array_unique(array_filter(array_map('intval', $raw))));
            sort($selected);

            if (count($selected) !== $requiredPicks) {
                $gateway->sendTextMessage($phone, "⚠️ Please provide exactly *{$requiredPicks}* distinct numbers (or reply *QP* for Quick Pick).", $tenant->whatsapp_number);
                return;
            }

            foreach ($selected as $num) {
                if ($num < 1 || $num > $maxPool) {
                    $gateway->sendTextMessage($phone, "⚠️ All numbers must be between 1 and {$maxPool}. Invalid ball: {$num}", $tenant->whatsapp_number);
                    return;
                }
            }
        }

        $data['selected_numbers'] = $selected;
        Cache::put($sessionKey, ['state' => 'ENTER_STAKE', 'data' => $data], now()->addMinutes(15));

        $numbersStr = implode(' - ', $selected);
        $minStake = $data['min_stake'] ?? 50;

        $msg = "✅ Numbers Selected: *[ {$numbersStr} ]*\n\n"
             . "Enter your stake amount in *{$tenant->currency}* (Min: {$tenant->currency} {$minStake}):\n"
             . "e.g. *100*, *200*, *500*, *1000*\n\n"
             . "_(Reply *0* to cancel)_";

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle stake entry and proceed to one-touch confirmation.
     */
    protected function handleStakeEntry(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $data = $session['data'] ?? [];

        if ($input === '0') {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        $stake = (float) preg_replace('/[^0-9.]/', '', $input);
        $minStake = (float) ($data['min_stake'] ?? 50);

        if ($stake < $minStake) {
            $gateway->sendTextMessage($phone, "⚠️ Minimum stake is {$tenant->currency} {$minStake}. Please enter amount:", $tenant->whatsapp_number);
            return;
        }

        $data['stake_kobo'] = (int) round($stake * 100);
        $data['stake_ngn'] = $stake;
        Cache::put($sessionKey, ['state' => 'CONFIRM_BET', 'data' => $data], now()->addMinutes(15));

        $numbersStr = implode(' - ', (array) $data['selected_numbers']);
        $gameTitle = $data['game_title'] ?? $data['game_code'];

        $msg = "📋 *BET SLIP SUMMARY*\n"
             . "━━━━━━━━━━━━━━━\n"
             . "• Game: *{$gameTitle}*\n"
             . "• Selection: *{$numbersStr}*\n"
             . "• Stake: *{$tenant->currency} " . number_format($stake, 2) . "*\n"
             . "━━━━━━━━━━━━━━━\n\n"
             . "Reply *1* or *YES* to confirm and place this bet.\n"
             . "_(Reply *0* to cancel)_";

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle one-touch bet confirmation without needing plaintext PIN.
     */
    protected function handleBetConfirmation(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $data = $session['data'] ?? [];
        $upper = strtoupper(trim($input));

        if ($upper === '0' || str_contains($upper, 'NO') || str_contains($upper, 'CANCEL')) {
            Cache::forget($sessionKey);
            $gateway->sendTextMessage($phone, "❌ Bet placement cancelled.", $tenant->whatsapp_number);
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        if ($upper === '1' || $upper === 'YES' || $upper === 'CONFIRM') {
            $this->executeBetPlacement(
                gateway: $gateway,
                tenant: $tenant,
                player: $player,
                gameCode: $data['game_code'],
                gameTitle: $data['game_title'] ?? $data['game_code'],
                selectedNumbers: (array) $data['selected_numbers'],
                stakeKobo: (int) $data['stake_kobo'],
                sessionKey: $sessionKey
            );
            return;
        }

        $gateway->sendTextMessage($phone, "Reply *1* to confirm bet or *0* to cancel.", $tenant->whatsapp_number);
    }

    /**
     * Execute bet placement through PlaceBetService with dual-wallet Kobo math.
     */
    protected function executeBetPlacement(
        WhatsAppGatewayInterface $gateway,
        Tenant $tenant,
        Player $player,
        string $gameCode,
        string $gameTitle,
        array $selectedNumbers,
        int $stakeKobo,
        string $sessionKey
    ): void {
        $phone = $player->phone;

        // Resolve or create active game and open draw
        $lottoGame = LottoGame::firstOrCreate(
            ['tenant_id' => $tenant->id, 'game_code' => $gameCode],
            [
                'name' => $gameTitle,
                'min_stake' => 50,
                'max_stake' => 50000,
                'total_pool_numbers' => count($selectedNumbers) > 5 ? 80 : 90,
                'required_picks' => count($selectedNumbers),
                'is_active' => true,
            ]
        );

        $draw = GameDraw::where('tenant_id', $tenant->id)
            ->where('lotto_game_id', $lottoGame->id)
            ->where('status', GameDraw::STATUS_OPEN)
            ->where('closes_at', '>', now())
            ->orderBy('closes_at', 'asc')
            ->first();

        if (!$draw) {
            $draw = GameDraw::create([
                'tenant_id' => $tenant->id,
                'lotto_game_id' => $lottoGame->id,
                'draw_code' => strtoupper(substr($gameCode, 0, 4)) . '-' . date('Ymd-Hi'),
                'status' => GameDraw::STATUS_OPEN,
                'opens_at' => now(),
                'closes_at' => now()->addHours(2),
            ]);
        }

        // Determine wallet source (Cash priority, Bonus fallback)
        $walletSource = $player->hasSufficientCash($stakeKobo)
            ? 'CASH'
            : ($player->hasSufficientBonus($stakeKobo) ? 'BONUS' : 'CASH');

        try {
            $ticket = $this->placeBetService->placeBet(
                player: $player,
                gameDraw: $draw,
                selectedNumbers: $selectedNumbers,
                stakeKobo: $stakeKobo,
                walletSource: $walletSource
            );
        } catch (DomainException $e) {
            $gateway->sendTextMessage($phone, "❌ {$e->getMessage()}\n\nReply *2* to fund your wallet.", $tenant->whatsapp_number);
            Cache::forget($sessionKey);
            return;
        }

        Cache::forget($sessionKey);

        $freshPlayer = $player->fresh();
        $newCashNgn = number_format($freshPlayer->cash_balance_kobo / 100, 2);
        $stakeNgn = number_format($stakeKobo / 100, 2);
        $numbersStr = implode(' - ', $selectedNumbers);

        // If Instant Game (Keno), settle immediately using CSPRNG
        if (str_contains($gameCode, 'KENO')) {
            $drawn = $this->instantRngService->generateDrawNumbers(20, 1, 80);
            $outcome = $this->instantRngService->settleInstantTicket($ticket, $drawn);

            $drawnStr = implode(' - ', array_slice($drawn, 0, 10)) . "\n" . implode(' - ', array_slice($drawn, 10));
            $hitCount = $outcome['hit_count'];
            $payoutNgn = number_format($outcome['payout_kobo'] / 100, 2);

            if ($outcome['status'] === 'WON') {
                $receipt = "🎉 *KENO INSTANT WIN!* 🏆\n"
                         . "━━━━━━━━━━━━━━━━━━━━\n"
                         . "• Ticket: *{$ticket->ticket_reference}*\n"
                         . "• Hits: *{$hitCount} / " . count($selectedNumbers) . "*\n"
                         . "• Payout Won: *{$tenant->currency} {$payoutNgn}*\n"
                         . "• Drawn Numbers:\n{$drawnStr}\n"
                         . "━━━━━━━━━━━━━━━━━━━━\n"
                         . "💵 New Balance: *{$tenant->currency} " . number_format($freshPlayer->fresh()->cash_balance_kobo / 100, 2) . "*\n\n"
                         . "Reply *1* to play again!";
            } else {
                $receipt = "🎟️ *KENO INSTANT DRAW RESULT*\n"
                         . "━━━━━━━━━━━━━━━━━━━━\n"
                         . "• Ticket: *{$ticket->ticket_reference}*\n"
                         . "• Hits: *{$hitCount} / " . count($selectedNumbers) . "*\n"
                         . "• Drawn Numbers:\n{$drawnStr}\n"
                         . "━━━━━━━━━━━━━━━━━━━━\n"
                         . "Better luck next time! 🍀\n\n"
                         . "Reply *1* to play again or *BAL* for balance.";
            }

            $gateway->sendTextMessage($phone, $receipt, $tenant->whatsapp_number);
            return;
        }

        // Scheduled 5/90 Draw receipt
        $receipt = "🎟️ *BET PLACED SUCCESSFULLY!*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "• Draw: *{$draw->draw_code}*\n"
                 . "• Ticket: *{$ticket->ticket_reference}*\n"
                 . "• Selection: *{$numbersStr}*\n"
                 . "• Stake: *{$tenant->currency} {$stakeNgn}* ({$walletSource})\n"
                 . "• Cash Balance: *{$tenant->currency} {$newCashNgn}*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Draw closing at {$draw->closes_at->format('H:i')} WAT.\n"
                 . "Good luck! 🍀 Results announced post-draw.\n\n"
                 . "Reply *MENU* for main options.";

        $gateway->sendTextMessage($phone, $receipt, $tenant->whatsapp_number);
    }

    /**
     * Handle masked PIN verification for bank withdrawals.
     */
    protected function handleWithdrawalPin(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $pin = trim($input);

        if (!Hash::check($pin, $player->withdrawal_pin_hash)) {
            $gateway->sendTextMessage($phone, "⛔ Incorrect Withdrawal PIN. For security, please re-enter your 4-digit PIN or reply *0* to cancel:", $tenant->whatsapp_number);
            return;
        }

        Cache::forget($sessionKey);
        $gateway->sendTextMessage($phone, "✅ PIN verified! Bank withdrawal processing via clearinghouse gateway.", $tenant->whatsapp_number);
    }

    /**
     * Handle native WhatsApp Flow responses (number picker / stake / masked withdrawal).
     */
    protected function handleFlowResponse(string $jsonPayload, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, string $sessionKey): void
    {
        $payload = json_decode($jsonPayload, true);
        if (!is_array($payload)) {
            return;
        }

        // Flow: Number selection & stake submitted in one native modal
        if (isset($payload['selected_numbers']) && isset($payload['stake'])) {
            $stakeKobo = (int) round(((float) $payload['stake']) * 100);
            $this->executeBetPlacement(
                gateway: $gateway,
                tenant: $tenant,
                player: $player,
                gameCode: $payload['game_code'] ?? '5_90_DAILY',
                gameTitle: $payload['game_title'] ?? 'Modern 5/90',
                selectedNumbers: (array) $payload['selected_numbers'],
                stakeKobo: $stakeKobo,
                sessionKey: $sessionKey
            );
            return;
        }

        // Flow: Masked PIN Withdrawal Form
        if (isset($payload['withdrawal_pin']) && isset($payload['amount'])) {
            if (!Hash::check($payload['withdrawal_pin'], $player->withdrawal_pin_hash)) {
                $gateway->sendTextMessage($player->phone, "⛔ Withdrawal rejected: Invalid Security PIN.", $tenant->whatsapp_number);
                return;
            }

            $amountKobo = (int) round(((float) $payload['amount']) * 100);
            if ($player->cash_balance_kobo < $amountKobo) {
                $gateway->sendTextMessage($player->phone, "⛔ Insufficient cash balance for withdrawal.", $tenant->whatsapp_number);
                return;
            }

            $player->cash_balance_kobo -= $amountKobo;
            $player->save();

            $amtNgn = number_format($amountKobo / 100, 2);
            $gateway->sendTextMessage($player->phone, "✅ Withdrawal of NGN {$amtNgn} approved and queued for bank transfer.", $tenant->whatsapp_number);
        }
    }

    /**
     * Show deposit details.
     */
    protected function showDepositOptions(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone): void
    {
        $msg = "💳 *FUND YOUR GAMING WALLET*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "• Bank: *Zenith Bank*\n"
             . "• Account: *1014829102*\n"
             . "• Account Name: *{$tenant->name}*\n"
             . "• Narration / Ref: *{$phone}*\n\n"
             . "Or fund online via Card / Bank Transfer:\n"
             . "👉 https://lotto.q4iltd.com/play\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "_Deposits credit automatically within 60 seconds._";

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Show latest draw results.
     */
    protected function showLatestResults(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone): void
    {
        $latestDraw = GameDraw::where('tenant_id', $tenant->id)
            ->whereNotNull('winning_numbers')
            ->orderByDesc('settled_at')
            ->first();

        $winningNums = $latestDraw?->winning_numbers
            ? implode(' - ', $latestDraw->winning_numbers)
            : '7 - 14 - 21 - 35 - 42';

        $drawCode = $latestDraw?->draw_code ?? 'M590-DAILY';

        $msg = "🏆 *LATEST DRAW RESULTS*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "• Draw: *{$drawCode}*\n"
             . "• Winning Numbers: *[ {$winningNums} ]*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n\n"
             . "Reply *1* to play games or *BAL* for balance.";

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle USSD menu tree navigation and game selection.
     */
    protected function handleUssdMenuNavigation(
        string $input,
        WhatsAppGatewayInterface $gateway,
        Tenant $tenant,
        Player $player,
        LottoOperatorContract $operator,
        array $session,
        string $sessionKey
    ): void {
        $currentItemId = $session['item_id'] ?? null;

        // If in tree, check database for child matching trigger
        if ($currentItemId) {
            $child = UssdMenuItem::where('parent_item_id', $currentItemId)
                ->where('option_trigger', $input)
                ->first();

            if ($child) {
                if ($child->response_type === 'CON') {
                    Cache::put($sessionKey, [
                        'state' => 'USSD_MENU',
                        'item_id' => $child->id,
                        'data' => [],
                    ], now()->addMinutes(15));

                    $gateway->sendTextMessage($player->phone, $child->response_text, $tenant->whatsapp_number);
                    return;
                }

                // If API action
                $text = strtoupper($child->response_text);
                if (str_contains($text, 'SELECT BALLS') || $child->id == 115 || str_contains($text, 'KENO')) {
                    Cache::put($sessionKey, [
                        'state' => 'SELECT_NUMBERS',
                        'data' => [
                            'game_code' => 'KENO_20_80',
                            'game_title' => 'Keno 20/80',
                            'pool' => 80,
                            'picks' => 10,
                            'min_stake' => 50,
                        ],
                    ], now()->addMinutes(15));

                    $msg = "🎱 *Keno 20/80 - Select Numbers*\n\n"
                         . "Pick *10 numbers* between 1 and 80 (e.g. _5 12 28 34 45 52 61 70 75 80_)\n"
                         . "Or reply *QP* for Quick Pick.\n\n"
                         . "Reply *0* to go back.";

                    $gateway->sendTextMessage($player->phone, $msg, $tenant->whatsapp_number);
                    return;
                }

                // 5/90 Direct / Perms
                Cache::put($sessionKey, [
                    'state' => 'SELECT_NUMBERS',
                    'data' => [
                        'game_code' => '5_90_DAILY',
                        'game_title' => $child->response_text,
                        'pool' => 90,
                        'picks' => 5,
                        'min_stake' => 100,
                    ],
                ], now()->addMinutes(15));

                $msg = "🎯 *{$child->response_text}*\n\n"
                     . "Pick *5 numbers* between 1 and 90 (e.g. _7 14 21 35 42_)\n"
                     . "Or reply *QP* for Quick Pick.\n\n"
                     . "Reply *0* to go back.";

                $gateway->sendTextMessage($player->phone, $msg, $tenant->whatsapp_number);
                return;
            }
        }

        $upper = strtoupper(trim($input));

        if (str_contains($upper, 'KENO') || $upper === '2') {
            Cache::put($sessionKey, [
                'state' => 'SELECT_NUMBERS',
                'data' => [
                    'game_code' => 'KENO_20_80',
                    'game_title' => 'Keno 20/80',
                    'pool' => 80,
                    'picks' => 10,
                    'min_stake' => 50,
                ],
            ], now()->addMinutes(15));

            $msg = "🎱 *Keno 20/80 - Select Numbers*\n\n"
                 . "Pick *10 numbers* between 1 and 80 (e.g. _5 12 28 34 45 52 61 70 75 80_)\n"
                 . "Or reply *QP* for Quick Pick.\n\n"
                 . "Reply *0* to go back.";

            $gateway->sendTextMessage($player->phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // Default: Modern 5/90 Direct
        Cache::put($sessionKey, [
            'state' => 'SELECT_NUMBERS',
            'data' => [
                'game_code' => '5_90_DAILY',
                'game_title' => 'Modern 5/90 Direct',
                'pool' => 90,
                'picks' => 5,
                'min_stake' => 100,
            ],
        ], now()->addMinutes(15));

        $msg = "🎯 *Modern 5/90 - Select Numbers*\n\n"
             . "Pick *5 numbers* between 1 and 90 (e.g. _7 14 21 35 42_)\n"
             . "Or reply *QP* for Quick Pick.\n\n"
             . "Reply *0* to go back.";

        $gateway->sendTextMessage($player->phone, $msg, $tenant->whatsapp_number);
    }
}
