<?php

namespace App\Services\Bot;

use App\Contracts\LottoOperatorContract;
use App\Contracts\WhatsAppGatewayInterface;
use App\DTOs\IncomingMessageDTO;
use App\Models\BetTicket;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Services\Ledger\LedgerService;
use App\Services\Operators\OperatorManager;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LottoConversationEngine
{
    public function __construct(
        protected WhatsAppManager $whatsAppManager,
        protected OperatorManager $operatorManager,
        protected LedgerService $ledgerService
    ) {}

    /**
     * Process an incoming message and advance the state machine.
     */
    public function handle(Tenant $tenant, IncomingMessageDTO $message): void
    {
        $gateway = $this->whatsAppManager->driver($tenant);
        $operator = $this->operatorManager->driver($tenant);
        $phone = $message->normalizedPhone();
        $input = $message->normalizedText();

        // 1. Fetch or create player
        $player = Player::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $phone],
            ['last_active_at' => now()]
        );
        $player->update(['last_active_at' => now()]);

        // 2. Fetch session state
        $sessionKey = "session:{$tenant->id}:{$phone}";
        $session = Cache::get($sessionKey, ['state' => 'USSD_MENU', 'item_id' => null, 'data' => []]);

        // Newcomer Onboarding State Machine
        if ($session['state'] === 'ONBOARDING_NAME') {
            $name = ucwords(strtolower(trim($input)));
            if (strlen($name) < 2 || is_numeric($name)) {
                $gateway->sendTextMessage($phone, "👋 Please reply with your first name or nickname (e.g. *Michael*):", $tenant->whatsapp_number);
                return;
            }
            $player->update(['name' => $name]);
            Cache::put($sessionKey, ['state' => 'ONBOARDING_PIN', 'data' => ['name' => $name]], now()->addMinutes(10));
            $msg = "Awesome to have you, *{$name}*! 🎉\n\n"
                 . "🔐 Choose a *4-digit Security PIN* to protect your wallet and confirm winning withdrawals (e.g. *1234*):";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        if ($session['state'] === 'ONBOARDING_PIN') {
            $pin = trim($input);
            if (strlen($pin) !== 4 || !ctype_digit($pin)) {
                $gateway->sendTextMessage($phone, "⚠️ Please enter a valid *4-digit numeric PIN* (e.g. *1234*):", $tenant->whatsapp_number);
                return;
            }
            $player->update(['pin_hash' => bcrypt($pin)]);
            Cache::forget($sessionKey);

            $wallet = $operator->checkWalletBalance($phone);
            $bal = number_format($wallet['balance'] ?? 0, 2);

            $msg = "✅ *Account Created Successfully!*\n"
                 . "━━━━━━━━━━━━━━━━\n"
                 . "👤 Account: *{$player->name}*\n"
                 . "📱 Phone: *+{$phone}*\n"
                 . "💰 Trial Wallet: *{$tenant->currency} {$bal}*\n"
                 . "━━━━━━━━━━━━━━━━\n\n"
                 . "You're all set to play and win! 🍀";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);

            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        // Global resets
        if (in_array($input, ['RESET', 'CANCEL', 'MENU', 'HOME', 'START', 'HI', 'HELLO'])) {
            // If newcomer with no name registered, initiate warm friendly onboarding
            if (empty($player->name)) {
                Cache::put($sessionKey, ['state' => 'ONBOARDING_NAME', 'data' => []], now()->addMinutes(10));
                $welcome = "🎰 *Welcome to {$tenant->name}!* 👋\n"
                         . "Nigeria's premier mobile lottery platform.\n\n"
                         . "Win Millions daily in our *Keno 20/80* and *Ghana 5/90* draws!\n\n"
                         . "To set up your gaming wallet:\n"
                         . "👉 *What is your first name or nickname?*";
                $gateway->sendTextMessage($phone, $welcome, $tenant->whatsapp_number);
                return;
            }

            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        // If player has no name but immediately sends a command/number, set default name
        if (empty($player->name)) {
            $player->update(['name' => 'Player ' . substr($phone, -4)]);
        }

        // Fast-path shortcuts (BAL, RES, KENO QP 200 1234, etc.)
        if ($this->handleShortcuts($input, $gateway, $tenant, $player, $operator, $sessionKey)) {
            return;
        }

        // Chained menu shortcuts (e.g. "1*1*1" or "5*1" or "1.1.1")
        if (preg_match('/^[0-9]+([\*\.][0-9]+)+#?$/', $input) || str_starts_with($input, '*347*102*') || str_starts_with($input, '*340*102*')) {
            $this->handleChainedOptionInput($input, $gateway, $tenant, $player, $operator, $sessionKey);
            return;
        }

        // 3. Dispatch based on current session state
        switch ($session['state']) {
            case 'USSD_MENU':
                $this->handleUssdMenuNavigation($input, $gateway, $tenant, $player, $operator, $session, $sessionKey);
                break;

            case 'SELECT_NUMBERS':
                $this->handleNumberSelection($input, $gateway, $tenant, $player, $session, $sessionKey);
                break;

            case 'ENTER_STAKE':
                $this->handleStakeEntry($input, $gateway, $tenant, $player, $session, $sessionKey);
                break;

            case 'CONFIRM_BET':
                $this->handleBetConfirmation($input, $gateway, $tenant, $player, $operator, $session, $sessionKey);
                break;

            default:
                $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
                break;
        }
    }

    /**
     * Display the Root USSD Menu (Item ID: 112).
     */
    protected function showRootMenu(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone, string $sessionKey, ?Player $player = null): void
    {
        $menu = UssdMenu::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        $rootItem = $menu?->rootItem();

        if (!$rootItem) {
            $gateway->sendTextMessage($phone, "🎰 Welcome to {$tenant->name}!\nReply *1* to Play Games or *2* for Results.", $tenant->whatsapp_number);
            return;
        }

        Cache::put($sessionKey, [
            'state' => 'USSD_MENU',
            'item_id' => $rootItem->id,
            'data' => [],
        ], now()->addMinutes(15));

        $greeting = $player && !empty($player->name)
            ? "🎰 *Welcome back, {$player->name}!* 🌟\n━━━━━━━━━━━━━━━━\n"
            : "🎰 *{$tenant->name}*\n━━━━━━━━━━━━━━━━\n";

        $gateway->sendTextMessage($phone, $greeting . $rootItem->response_text, $tenant->whatsapp_number);
    }

    /**
     * Handle USSD Tree Navigation (CON vs API).
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
        $phone = $player->phone;
        $currentItemId = $session['item_id'] ?? null;

        if (!$currentItemId) {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
            return;
        }

        $currentItem = UssdMenuItem::find($currentItemId);
        if (!$currentItem) {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
            return;
        }

        // Handle "0" (Back button)
        if ($input === '0') {
            if ($currentItem->parent_item_id) {
                $parent = UssdMenuItem::find($currentItem->parent_item_id);
                if ($parent) {
                    Cache::put($sessionKey, [
                        'state' => 'USSD_MENU',
                        'item_id' => $parent->id,
                        'data' => [],
                    ], now()->addMinutes(15));
                    $gateway->sendTextMessage($phone, $parent->response_text, $tenant->whatsapp_number);
                    return;
                }
            }
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
            return;
        }

        // Find child node matching the option trigger
        $child = UssdMenuItem::where('parent_item_id', $currentItem->id)
            ->where('option_trigger', $input)
            ->first();

        if (!$child) {
            $gateway->sendTextMessage(
                $phone,
                "⚠️ Invalid option *{$input}*.\n\n" . $currentItem->response_text,
                $tenant->whatsapp_number
            );
            return;
        }

        // If node is a Continue menu (CON)
        if ($child->response_type === 'CON') {
            Cache::put($sessionKey, [
                'state' => 'USSD_MENU',
                'item_id' => $child->id,
                'data' => [],
            ], now()->addMinutes(15));

            $gateway->sendTextMessage($phone, $child->response_text, $tenant->whatsapp_number);
            return;
        }

        // If node is an API Action (API)
        $this->executeUssdApiAction($child, $gateway, $tenant, $player, $operator, $sessionKey);
    }

    /**
     * Execute an API action node based on the USSD menu hierarchy.
     */
    protected function executeUssdApiAction(
        UssdMenuItem $item,
        WhatsAppGatewayInterface $gateway,
        Tenant $tenant,
        Player $player,
        LottoOperatorContract $operator,
        string $sessionKey
    ): void {
        $phone = $player->phone;
        $text = strtoupper($item->response_text);

        // 1. KENO: Select Balls
        if (str_contains($text, 'SELECT BALLS') || $item->id == 115) {
            Cache::put($sessionKey, [
                'state' => 'SELECT_NUMBERS',
                'data' => [
                    'game_code' => 'KENO_20_80',
                    'game_title' => 'Keno 20/80 (Awoof)',
                    'pool' => 80,
                    'picks' => 10,
                    'min_stake' => 50,
                ],
            ], now()->addMinutes(10));

            $msg = "🎱 *Keno 20/80 - Select Balls*\n\n"
                 . "Pick *10 numbers* between 1 and 80 (e.g. _5 12 28 34 45 52 61 70 75 80_)\n"
                 . "Or reply *QP* for Quick Pick.\n\n"
                 . "Reply *0* to go Back.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 2. KENO: More Games (SUM BIG, SUM SMALL, ELEMENTS, ODDS)
        if (str_contains($text, 'SUM') || str_contains($text, 'ODDS') || in_array($item->id, range(117, 125))) {
            $gameName = $item->response_text;
            Cache::put($sessionKey, [
                'state' => 'ENTER_STAKE',
                'data' => [
                    'game_code' => 'KENO_' . strtoupper(Str::slug($gameName, '_')),
                    'game_title' => $gameName,
                    'selected_numbers' => [$gameName],
                    'min_stake' => 50,
                ],
            ], now()->addMinutes(10));

            $msg = "🎲 *{$gameName} Selected!*\n\n"
                 . "Enter your stake amount in *{$tenant->currency}* (Minimum: {$tenant->currency} 50):\n"
                 . "e.g. *100*, *200*, *500*, *1000*\n\n"
                 . "Reply *0* to cancel.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 3. 5/90 PERM / 1BANKER / 1ST DRAWN (Modern Billionaire, Jackpot, Ghana 5/90, VAG, Noon Rush)
        if (str_contains($text, 'PERM') || str_contains($text, 'BANKER') || str_contains($text, '1ST DRAWN')) {
            $parentItem = $item->parent;
            $gameFamily = $parentItem ? $parentItem->response_text : '5/90 Lotto';
            $betType = $item->option_trigger; // "1" for PERM1, "2" for PERM2, etc.

            $picks = match ($item->option_trigger) {
                '1', '6', '7' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                default => 2,
            };

            Cache::put($sessionKey, [
                'state' => 'SELECT_NUMBERS',
                'data' => [
                    'game_code' => '5_90_' . strtoupper(Str::slug($item->response_text, '_')),
                    'game_title' => $item->response_text,
                    'pool' => 90,
                    'picks' => $picks,
                    'min_stake' => 100,
                ],
            ], now()->addMinutes(10));

            $msg = "🎯 *{$item->response_text} Selected!*\n\n"
                 . "Pick *{$picks} number(s)* between 1 and 90 (e.g. _" . implode(' ', range(5, 5 + $picks - 1)) . "_)\n"
                 . "Or reply *QP* for Quick Pick.\n\n"
                 . "Reply *0* to go Back.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 4. CHECK BALANCE (Credit Balance, Masters Balance, Bonus)
        if (str_contains($text, 'BALANCE') || in_array($item->id, [294, 295, 296])) {
            $wallet = $operator->checkWalletBalance($phone);
            $creditBal = number_format($wallet['balance'] ?? 0, 2);
            $mastersBal = "0.00";
            $bonusBal = "150.00";

            $balanceMsg = match ($item->id) {
                295 => "💼 *Masters Balance*\n• Available: *{$tenant->currency} {$mastersBal}*",
                296 => "🎁 *Bonus Balance*\n• Available: *{$tenant->currency} {$bonusBal}*",
                default => "💰 *Credit Balance*\n• Available: *{$tenant->currency} {$creditBal}*",
            };

            $msg = "{$balanceMsg}\n\nReply *0* to return to menu or *MENU* for main options.";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 5. RECHARGE (Online vs Bank Transfer)
        if (str_contains($text, 'RECHARGE') || in_array($item->id, [288, 289])) {
            if ($item->id == 288) { // Online
                $msg = "💳 *Online Recharge*\n\n"
                     . "Top up your wallet instantly using your debit card or mobile transfer:\n"
                     . "👉 https://modernlottery.ng/deposit?phone={$phone}\n\n"
                     . "Reply *0* for menu.";
            } else { // Transfer
                $msg = "🏦 *Bank Transfer Recharge*\n\n"
                     . "• Bank: *Zenith Bank*\n"
                     . "• Account Number: *1014829102*\n"
                     . "• Account Name: *Modern Lottery Limited*\n"
                     . "• Narration / Reference: *{$phone}*\n\n"
                     . "_Your wallet will be credited automatically within 2 minutes._\n\n"
                     . "Reply *0* for menu.";
            }

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 6. WITHDRAW (Select Card / Add Card)
        if (str_contains($text, 'BANK CARD') || in_array($item->id, [291, 292])) {
            $msg = "🏦 *Withdrawals*\n\n"
                 . "To link or manage your payout bank account securely:\n"
                 . "👉 https://modernlottery.ng/wallet/withdraw\n\n"
                 . "Minimum withdrawal: {$tenant->currency} 1,000.00\n\n"
                 . "Reply *0* for menu.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 7. CHECK RESULTS
        if (str_contains($text, 'RESULT') || in_array($item->id, range(264, 286))) {
            $res = $operator->getResults('5_90_DAILY');
            $winningNums = implode(' - ', $res['winning_numbers'] ?? [7, 14, 21, 35, 42]);

            $msg = "🏆 *Results: {$item->response_text}*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "• Draw Date: {$res['draw_date']}\n"
                 . "• Winning Numbers: *[ {$winningNums} ]*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n\n"
                 . "Reply *1* to Play Games or *0* to return.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // 8. CONTACT US
        if (in_array($item->id, [297])) {
            $msg = "📞 *Modern Lottery Support*\n\n"
                 . "• Website: https://modernlottery.ng\n"
                 . "• WhatsApp: +234 812 345 6789\n"
                 . "• Helpline: 0700-MODERN-LOTTO\n"
                 . "• Hours: 24/7 Daily\n\n"
                 . "Reply *0* for menu.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        // Generic API Response
        $gateway->sendTextMessage($phone, "✅ {$item->response_text}\n\nReply *0* for menu.", $tenant->whatsapp_number);
    }

    /**
     * Handle chained quick menu selections (e.g. "1*1*1" or "5.1").
     */
    protected function handleChainedOptionInput(
        string $dialString,
        WhatsAppGatewayInterface $gateway,
        Tenant $tenant,
        Player $player,
        LottoOperatorContract $operator,
        string $sessionKey
    ): void {
        // Strip prefix, trailing symbols, and split by * or .
        $clean = trim($dialString, '*#.');
        $clean = str_replace('.', '*', $clean);
        $segments = explode('*', $clean);

        // Remove legacy service code prefix if present
        if (($segments[0] ?? '') === '347' && ($segments[1] ?? '') === '102') {
            $steps = array_slice($segments, 2);
        } else {
            $steps = $segments;
        }

        $menu = UssdMenu::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        $current = $menu?->rootItem();

        if (empty($steps)) {
            $this->showRootMenu($gateway, $tenant, $player->phone, $sessionKey);
            return;
        }

        foreach ($steps as $step) {
            if (!$current) break;
            $child = UssdMenuItem::where('parent_item_id', $current->id)
                ->where('option_trigger', $step)
                ->first();
            if ($child) {
                $current = $child;
            } else {
                break;
            }
        }

        if ($current) {
            if ($current->response_type === 'CON') {
                Cache::put($sessionKey, [
                    'state' => 'USSD_MENU',
                    'item_id' => $current->id,
                    'data' => [],
                ], now()->addMinutes(15));
                $gateway->sendTextMessage($player->phone, $current->response_text, $tenant->whatsapp_number);
            } else {
                $this->executeUssdApiAction($current, $gateway, $tenant, $player, $operator, $sessionKey);
            }
        }
    }

    /**
     * Handle number selection (Balls or Quick Pick).
     */
    protected function handleNumberSelection(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $data = $session['data'];
        $requiredPicks = $data['picks'];
        $maxPool = $data['pool'];

        if ($input === '0') {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
            return;
        }

        if ($input === 'QP' || str_contains($input, 'QUICK')) {
            $numbers = range(1, $maxPool);
            shuffle($numbers);
            $selected = array_slice($numbers, 0, $requiredPicks);
            sort($selected);
        } else {
            $raw = preg_split('/[\s,\-\.]+/', $input);
            $selected = array_values(array_unique(array_filter(array_map('intval', $raw))));
            sort($selected);

            if (count($selected) !== $requiredPicks) {
                $gateway->sendTextMessage($phone, "⚠️ Please provide exactly *{$requiredPicks}* distinct numbers (or reply *QP*).", $tenant->whatsapp_number);
                return;
            }

            foreach ($selected as $num) {
                if ($num < 1 || $num > $maxPool) {
                    $gateway->sendTextMessage($phone, "⚠️ All numbers must be between 1 and {$maxPool}. Invalid: {$num}", $tenant->whatsapp_number);
                    return;
                }
            }
        }

        $data['selected_numbers'] = $selected;
        Cache::put($sessionKey, ['state' => 'ENTER_STAKE', 'data' => $data], now()->addMinutes(10));

        $numbersStr = implode(' - ', $selected);
        $msg = "✅ Numbers Selected: *[ {$numbersStr} ]*\n\n"
             . "Enter your stake in *{$tenant->currency}* (Minimum: {$tenant->currency} {$data['min_stake']}):\n"
             . "e.g. *100*, *200*, *500*, *1000*\n\n"
             . "Reply *0* to cancel.";

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle stake entry.
     */
    protected function handleStakeEntry(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $data = $session['data'];

        if ($input === '0') {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
            return;
        }

        $stake = (float)preg_replace('/[^0-9.]/', '', $input);

        if ($stake < ($data['min_stake'] ?? 50)) {
            $gateway->sendTextMessage($phone, "⚠️ Minimum stake is {$tenant->currency} {$data['min_stake']}. Please enter amount:", $tenant->whatsapp_number);
            return;
        }

        $data['stake'] = $stake;
        Cache::put($sessionKey, ['state' => 'CONFIRM_BET', 'data' => $data], now()->addMinutes(10));

        $numbersStr = is_array($data['selected_numbers']) ? implode(' - ', $data['selected_numbers']) : $data['selected_numbers'];
        $gameTitle = $data['game_title'] ?? $data['game_code'];

        $msg = "📋 *BET SLIP SUMMARY*\n"
             . "━━━━━━━━━━━━━━━\n"
             . "• Game: *{$gameTitle}*\n"
             . "• Selection: *{$numbersStr}*\n"
             . "• Stake: *{$tenant->currency} " . number_format($stake, 2) . "*\n"
             . "━━━━━━━━━━━━━━━\n\n"
             . "🔐 Enter your *4-digit Security PIN* to place this bet:\n"
             . "_(Reply *0* to cancel)_";

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle bet authorization with 4-digit PIN.
     */
    protected function handleBetConfirmation(
        string $input,
        WhatsAppGatewayInterface $gateway,
        Tenant $tenant,
        Player $player,
        LottoOperatorContract $operator,
        array $session,
        string $sessionKey
    ): void {
        $phone = $player->phone;
        $data = $session['data'];

        if ($input === '0') {
            Cache::forget($sessionKey);
            $gateway->sendTextMessage($phone, "❌ Bet cancelled. Returning to main menu.", $tenant->whatsapp_number);
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey);
            return;
        }

        $pin = trim($input);
        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            $gateway->sendTextMessage($phone, "⚠️ Invalid PIN format. Please enter your 4-digit PIN:", $tenant->whatsapp_number);
            return;
        }

        if (!$operator->verifyPin($phone, $pin)) {
            $gateway->sendTextMessage($phone, "⛔ Incorrect Security PIN. Please try again or reply *0* to cancel:", $tenant->whatsapp_number);
            return;
        }

        $idempotencyKey = (string)Str::uuid();
        $ticketRef = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $ticket = BetTicket::create([
            'tenant_id' => $tenant->id,
            'player_id' => $player->id,
            'ticket_reference' => $ticketRef,
            'game_code' => $data['game_code'],
            'selected_numbers' => $data['selected_numbers'],
            'stake_amount' => $data['stake'],
            'q4i_share' => 0,
            'operator_share' => 0,
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey,
        ]);

        $response = $operator->placeBet(
            phone: $phone,
            gameCode: $data['game_code'],
            numbers: (array)$data['selected_numbers'],
            stake: $data['stake'],
            idempotencyKey: $idempotencyKey
        );

        if (!$response['success']) {
            $ticket->update([
                'status' => 'rejected',
                'raw_operator_response' => $response,
            ]);

            $gateway->sendTextMessage($phone, "❌ Bet placement failed: {$response['message']}", $tenant->whatsapp_number);
            Cache::forget($sessionKey);
            return;
        }

        $ticket->update([
            'status' => 'accepted',
            'operator_ticket_id' => $response['ticket_id'] ?? null,
            'raw_operator_response' => $response,
        ]);

        // Record in the cryptographic double-entry ledger (35% Q4I / 65% Operator)
        $this->ledgerService->recordBetPlacement($ticket);

        Cache::forget($sessionKey);

        $numbersStr = is_array($data['selected_numbers']) ? implode(' - ', $data['selected_numbers']) : $data['selected_numbers'];
        $newBal = isset($response['balance_after']) ? number_format($response['balance_after'], 2) : 'N/A';
        $gameTitle = $data['game_title'] ?? $data['game_code'];

        $receipt = "🎟️ *BET PLACED SUCCESSFULLY!*\n"
                 . "━━━━━━━━━━━━━━━━━━━\n"
                 . "• Ticket: *{$ticketRef}*\n"
                 . "• Operator Ref: *{$ticket->operator_ticket_id}*\n"
                 . "• Game: *{$gameTitle}*\n"
                 . "• Selection: *{$numbersStr}*\n"
                 . "• Stake: *{$tenant->currency} " . number_format($data['stake'], 2) . "*\n"
                 . "• New Balance: *{$tenant->currency} {$newBal}*\n"
                 . "━━━━━━━━━━━━━━━━━━━\n"
                 . "Good luck! 🍀 Results announced after draw.\n\n"
                 . "Reply *MENU* to place another bet.";

        $gateway->sendTextMessage($phone, $receipt, $tenant->whatsapp_number);
    }

    /**
     * Power-User Fast Shortcuts (BAL, RES, KENO QP 200 1234, etc.).
     */
    protected function handleShortcuts(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, LottoOperatorContract $operator, string $sessionKey): bool
    {
        $phone = $player->phone;

        if (in_array($input, ['BAL', 'BALANCE', 'WAL', 'WALLET'])) {
            $wallet = $operator->checkWalletBalance($phone);
            $bal = number_format($wallet['balance'] ?? 0, 2);
            $msg = "💰 *Credit Balance: {$tenant->currency} {$bal}*\n\nReply *MENU* to view games.";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return true;
        }

        if (in_array($input, ['RES', 'RESULT', 'RESULTS'])) {
            $res = $operator->getResults('5_90_DAILY');
            $winningNums = implode(' - ', $res['winning_numbers'] ?? []);
            $msg = "🏆 *Latest Draw Results (5/90)*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "• Date: {$res['draw_date']}\n"
                 . "• Winning Numbers: *[ {$winningNums} ]*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n\n"
                 . "Reply *1* to play games.";
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return true;
        }

        // Fast-bet single line
        $tokens = preg_split('/[\s,\*#]+/', $input);
        $tokens = array_values(array_filter($tokens));

        if (count($tokens) >= 4) {
            $first = strtoupper($tokens[0]);
            if (in_array($first, ['KENO', '590', '5/90', '102'])) {
                if ($first === '102') {
                    array_shift($tokens);
                    $first = strtoupper($tokens[0] ?? '');
                }

                $gameCode = match ($first) {
                    'KENO', '1' => 'KENO_20_80',
                    '590', '5/90', '2' => '5_90_DAILY',
                    default => null,
                };

                if ($gameCode) {
                    $pin = end($tokens);
                    $stake = (float)prev($tokens);
                    $midTokens = array_slice($tokens, 1, -2);

                    $isQP = (count($midTokens) === 1 && strtoupper($midTokens[0]) === 'QP');
                    $pool = ($gameCode === 'KENO_20_80') ? 80 : 90;
                    $picks = ($gameCode === 'KENO_20_80') ? 10 : 5;

                    if ($isQP) {
                        $nums = range(1, $pool);
                        shuffle($nums);
                        $selected = array_slice($nums, 0, $picks);
                        sort($selected);
                    } else {
                        $selected = array_values(array_unique(array_filter(array_map('intval', $midTokens))));
                        sort($selected);
                    }

                    if (count($selected) === $picks && $stake >= 50 && strlen((string)$pin) === 4) {
                        $sessionData = [
                            'game_code' => $gameCode,
                            'game_title' => $gameCode === 'KENO_20_80' ? 'Keno 20/80 (Awoof)' : '5/90 Lotto',
                            'selected_numbers' => $selected,
                            'stake' => $stake,
                        ];

                        $this->handleBetConfirmation(
                            input: (string)$pin,
                            gateway: $gateway,
                            tenant: $tenant,
                            player: $player,
                            operator: $operator,
                            session: ['data' => $sessionData],
                            sessionKey: $sessionKey
                        );
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
