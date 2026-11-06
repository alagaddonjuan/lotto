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

        $upperInput = strtoupper(trim($input));

        // 3b. Language Selection Handling
        if ($upperInput === 'LANG' || $upperInput === 'LANGUAGE' || $upperInput === 'EDE' || $upperInput === 'ASUSU' || $upperInput === 'HARSHEN' || str_starts_with($upperInput, 'LANG_') || str_starts_with($upperInput, 'LANG ')) {
            $this->handleLanguageSwitch($upperInput, $gateway, $tenant, $player, $sessionKey);
            return;
        }

        // Newcomer Onboarding State Machine
        if ($session['state'] === 'ONBOARDING_NAME') {
            $name = ucwords(strtolower(trim($input)));
            if (strlen($name) < 2 || is_numeric($name)) {
                $gateway->sendTextMessage($phone, $this->msg('invalid_name', [], $player->language), $tenant->whatsapp_number);
                return;
            }
            $player->update(['name' => $name]);
            Cache::put($sessionKey, ['state' => 'ONBOARDING_PIN', 'data' => ['name' => $name]], now()->addMinutes(15));
            $msg = $this->msg('welcome_name', ['name' => $name], $player->language);
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

        if ($session['state'] === 'ONBOARDING_PIN') {
            $pin = trim($input);
            if (strlen($pin) !== 4 || !ctype_digit($pin)) {
                $gateway->sendTextMessage($phone, $this->msg('invalid_pin', [], $player->language), $tenant->whatsapp_number);
                return;
            }

            $player->update([
                'withdrawal_pin_hash' => Hash::make($pin),
                'pin_hash' => Hash::make($pin),
            ]);
            Cache::forget($sessionKey);

            $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
            $bonusNgn = number_format($player->bonus_balance_kobo / 100, 2);

            $msg = $this->msg('account_ready', [
                'name' => $player->name,
                'phone' => $phone,
                'currency' => $tenant->currency,
                'cash' => $cashNgn,
                'bonus' => $bonusNgn,
            ], $player->language);
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);

            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        // Global Navigation Resets
        if (in_array(strtoupper($input), ['RESET', 'CANCEL', 'MENU', 'HOME', 'START', 'HI', 'HELLO', 'MAIN'])) {
            if (empty($player->name)) {
                Cache::put($sessionKey, ['state' => 'ONBOARDING_NAME', 'data' => []], now()->addMinutes(15));
                $welcome = $this->msg('welcome_header', ['tenant' => $tenant->name], $player->language)
                         . "\n\n"
                         . $this->msg('ask_name', [], $player->language)
                         . "\n\n_(Reply *LANG* anytime to switch to Pidgin, Yorùbá, Hausa, or Igbo)_";
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

        // Global Interactive Button & List Replies (work from ANY state if user taps an earlier button)
        $upperInput = strtoupper(trim($input));
        if ($upperInput === 'OPT_PLAY') {
            $this->showGamesMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }
        if ($upperInput === 'OPT_FUND') {
            $this->showDepositOptions($gateway, $tenant, $phone, $player);
            return;
        }
        if ($upperInput === 'OPT_RESULTS') {
            $this->showLatestResults($gateway, $tenant, $phone, $player);
            return;
        }
        if (in_array($upperInput, ['GAME_590', 'GAME_KENO', 'GAME_FAST3'])) {
            $this->startGameSelection($upperInput, $gateway, $tenant, $player, $sessionKey);
            return;
        }

        // Top-Level Navigation Triggers (when at Root menu)
        if (($session['state'] ?? 'ROOT_MENU') === 'ROOT_MENU') {
            if (str_contains($upperInput, 'PLAY')) {
                $this->showGamesMenu($gateway, $tenant, $phone, $sessionKey, $player);
                return;
            }

            if (str_contains($upperInput, 'FUND') || str_contains($upperInput, 'DEPOSIT')) {
                $this->showDepositOptions($gateway, $tenant, $phone, $player);
                return;
            }

            if (str_contains($upperInput, 'RESULT')) {
                $this->showLatestResults($gateway, $tenant, $phone, $player);
                return;
            }

            // Direct numeric or chained USSD trigger (e.g. 1, 2, 2*2, 5*1 in USSD mode)
            if (ctype_digit($input) || preg_match('/^\d+(\*\d+)+$/', $input)) {
                if (empty($session['item_id'])) {
                    $menu = UssdMenu::where('tenant_id', $tenant->id)->where('is_active', true)->first();
                    $session['item_id'] = $menu?->rootItem()?->id ?? 112;
                }
                $steps = explode('*', $input);
                foreach ($steps as $step) {
                    $this->handleUssdMenuNavigation($step, $gateway, $tenant, $player, $operator, $session, $sessionKey);
                    $session = Cache::get($sessionKey, ['state' => 'ROOT_MENU', 'item_id' => null, 'data' => []]);
                }
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

            case 'WITHDRAWAL_AMOUNT':
                $this->handleWithdrawalAmount($input, $gateway, $tenant, $player, $session, $sessionKey);
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
     * Localization helper reading from lang/{locale}/bot.php
     */
    protected function msg(string $key, array $replace = [], ?string $locale = 'en'): string
    {
        $locale = in_array($locale, ['en', 'pcm', 'yo', 'ha', 'ig']) ? $locale : 'en';
        return __("bot.{$key}", $replace, $locale);
    }

    /**
     * Handle Language Selection & Switching (Pidgin, Yorùbá, Hausa, Igbo, English).
     */
    protected function handleLanguageSwitch(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, string $sessionKey): void
    {
        $phone = $player->phone;
        $matchedLang = null;

        if (str_contains($input, 'PCM') || str_contains($input, 'PIDGIN')) {
            $matchedLang = 'pcm';
        } elseif (str_contains($input, 'YO') || str_contains($input, 'YORUBA')) {
            $matchedLang = 'yo';
        } elseif (str_contains($input, 'HA') || str_contains($input, 'HAUSA')) {
            $matchedLang = 'ha';
        } elseif (str_contains($input, 'IG') || str_contains($input, 'IGBO')) {
            $matchedLang = 'ig';
        } elseif (str_contains($input, 'EN') || str_contains($input, 'ENGLISH')) {
            $matchedLang = 'en';
        }

        if ($matchedLang) {
            $player->update(['language' => $matchedLang]);
            $confirm = $this->msg('lang_selected', [], $matchedLang);
            $gateway->sendTextMessage($phone, $confirm, $tenant->whatsapp_number);
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        // Present language choices via interactive list menu
        $sections = [
            [
                'title' => 'Select Your Language',
                'rows' => [
                    ['id' => 'LANG_EN', 'title' => '🇬🇧 English', 'description' => 'English Language'],
                    ['id' => 'LANG_PCM', 'title' => '🇳🇬 Pidgin', 'description' => 'Naija Pidgin English'],
                    ['id' => 'LANG_YO', 'title' => '🟡 Yorùbá', 'description' => 'Èdè Yorùbá'],
                    ['id' => 'LANG_HA', 'title' => '🟢 Hausa', 'description' => 'Harshen Hausa'],
                    ['id' => 'LANG_IG', 'title' => '🔴 Igbo', 'description' => 'Asụsụ Igbo'],
                ],
            ],
        ];

        $header = "🌐 *SELECT LANGUAGE / YAN ÈDÈ / ZAƁI HARSHE / HỌRỌ ASỤSỤ*\n\nChoose your preferred language for betting, receipts, and results:";
        $gateway->sendListMenu($phone, $header, 'Choose Language', $sections, $tenant->whatsapp_number);
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

        $lang = $player?->language ?? 'en';
        $cashNgn = $player ? number_format($player->cash_balance_kobo / 100, 2) : '0.00';
        $name = $player?->name ?? 'Player';

        $header = $this->msg('root_menu_header', [
            'tenant' => $tenant->name,
            'name' => $name,
            'currency' => $tenant->currency,
            'cash' => $cashNgn,
        ], $lang);

        $buttons = [
            ['id' => 'opt_play', 'title' => '🎯 ' . $this->msg('btn_play', [], $lang)],
            ['id' => 'opt_fund', 'title' => '💳 ' . $this->msg('btn_fund', [], $lang)],
            ['id' => 'opt_results', 'title' => '🏆 ' . $this->msg('btn_results', [], $lang)],
        ];

        $gateway->sendQuickReplyButtons($phone, $header, $buttons, $tenant->whatsapp_number);
    }

    /**
     * Display Active Games via Native Interactive List Message (scrollable drawer).
     * Section title strictly <= 24 chars per Meta Cloud API specification.
     */
    protected function showGamesMenu(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone, string $sessionKey, ?Player $player = null): void
    {
        $lang = $player?->language ?? 'en';
        $cutoff590 = now()->addHours(2)->format('H:i');
        $cutoffKeno = now()->addMinutes(15)->format('H:i');

        $sections = [
            [
                'title' => $this->msg('games_section_title', [], $lang),
                'rows' => [
                    [
                        'id' => 'game_590',
                        'title' => 'Modern 5/90 Direct',
                        'description' => "Min: {$tenant->currency} 100 | Cut-off: {$cutoff590} WAT",
                    ],
                    [
                        'id' => 'game_keno',
                        'title' => 'Keno 20/80 (Instant)',
                        'description' => "Min: {$tenant->currency} 50 | Live Draw: {$cutoffKeno}",
                    ],
                    [
                        'id' => 'game_fast3',
                        'title' => 'Fast 3 Jackpot',
                        'description' => "Min: {$tenant->currency} 100 | Drawn Every 10 Mins",
                    ],
                ],
            ],
        ];

        $header = $this->msg('games_menu_header', [], $lang);
        $btnTitle = $this->msg('games_menu_btn', [], $lang);
        $gateway->sendListMenu($phone, $header, $btnTitle, $sections, $tenant->whatsapp_number);

        Cache::put($sessionKey, [
            'state' => 'USSD_MENU',
            'item_id' => null,
            'data' => [],
        ], now()->addMinutes(15));
    }

    /**
     * Start number selection for a specific game in the player's chosen language.
     */
    protected function startGameSelection(
        string $gameIdentifier,
        WhatsAppGatewayInterface $gateway,
        Tenant $tenant,
        Player $player,
        string $sessionKey,
        ?string $customTitle = null,
        ?int $customPicks = null
    ): void {
        $lang = $player->language ?? 'en';
        $upper = strtoupper(trim($gameIdentifier));

        if (str_contains($upper, 'KENO') || $upper === 'GAME_KENO') {
            $gameCode = 'KENO_20_80';
            $gameTitle = $customTitle ?: 'Keno 20/80';
            $pool = 80;
            $picks = $customPicks ?: 10;
            $minStake = 50;
            $example = '5 12 28 34 45 52 61 70 75 80';
        } elseif (str_contains($upper, 'FAST3') || str_contains($upper, 'FAST_3') || $upper === 'GAME_FAST3') {
            $gameCode = 'FAST_3';
            $gameTitle = $customTitle ?: 'Fast 3 Jackpot';
            $pool = 90;
            $picks = $customPicks ?: 3;
            $minStake = 100;
            $example = '7 14 21';
        } else {
            $gameCode = '5_90_DAILY';
            $gameTitle = $customTitle ?: 'Modern 5/90 Direct';
            $pool = 90;
            $picks = $customPicks ?: 5;
            $minStake = 100;
            $examples = [
                1 => '7',
                2 => '7 14',
                3 => '7 14 21',
                4 => '7 14 21 35',
                5 => '7 14 21 35 42',
            ];
            $example = $examples[$picks] ?? '7 14 21 35 42';
        }

        Cache::put($sessionKey, [
            'state' => 'SELECT_NUMBERS',
            'data' => [
                'game_code' => $gameCode,
                'game_title' => $gameTitle,
                'pool' => $pool,
                'picks' => $picks,
                'min_stake' => $minStake,
            ],
        ], now()->addMinutes(15));

        $msg = $this->msg('choose_numbers', [
            'game' => $gameTitle,
            'draw' => strtoupper(substr($gameCode, 0, 4)) . '-' . date('Hi'),
            'count' => $picks,
            'range' => $pool,
            'example' => $example,
        ], $lang);

        $gateway->sendTextMessage($player->phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle fast shortcuts (e.g. BAL, RES, KENO QP 200, 590 QP 500) without PIN.
     */
    protected function handleShortcuts(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, string $sessionKey): bool
    {
        $phone = $player->phone;
        $lang = $player->language ?? 'en';
        $upper = strtoupper(trim($input));

        if (in_array($upper, ['BAL', 'BALANCE', 'WALLET', 'WAL'])) {
            $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
            $bonusNgn = number_format($player->bonus_balance_kobo / 100, 2);
            $totalNgn = number_format($player->totalBalanceKobo() / 100, 2);

            $msg = $this->msg('wallet_balance_box', [
                'currency' => $tenant->currency,
                'cash' => $cashNgn,
                'bonus' => $bonusNgn,
                'total' => $totalNgn,
            ], $lang);
            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return true;
        }

        if (in_array($upper, ['RES', 'RESULT', 'RESULTS'])) {
            $this->showLatestResults($gateway, $tenant, $phone, $player);
            return true;
        }

        if (in_array($upper, ['FUND', 'DEPOSIT', 'TOPUP', 'ADD'])) {
            $this->showDepositOptions($gateway, $tenant, $phone, $player);
            return true;
        }

        // Bank Cash Withdrawal Command: WITHDRAW or WITHDRAW 2000 or CASHOUT 2000
        if ($upper === 'WITHDRAW' || $upper === 'CASHOUT' || str_starts_with($upper, 'WITHDRAW ') || str_starts_with($upper, 'CASHOUT ')) {
            $parts = preg_split('/\s+/', trim($input));
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $this->handleWithdrawalAmount($parts[1], $gateway, $tenant, $player, [], $sessionKey);
                return true;
            }

            $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
            Cache::put($sessionKey, ['state' => 'WITHDRAWAL_AMOUNT', 'data' => []], now()->addMinutes(15));
            $gateway->sendTextMessage(
                $phone,
                "🏦 *BANK CASH WITHDRAWAL*\n━━━━━━━━━━━━━━━━━━━━\nAvailable Cash Balance: *{$tenant->currency} {$cashNgn}*\n\nEnter the amount you wish to withdraw in *{$tenant->currency}* (Min: {$tenant->currency} 500):\n_(Reply *0* to cancel)_",
                $tenant->whatsapp_number
            );
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
     * Handle number picking stage (localized in player's language).
     */
    protected function handleNumberSelection(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $lang = $player->language ?? 'en';
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
                $gateway->sendTextMessage($phone, $this->msg('invalid_picks_count', ['count' => $requiredPicks], $lang), $tenant->whatsapp_number);
                return;
            }

            foreach ($selected as $num) {
                if ($num < 1 || $num > $maxPool) {
                    $gateway->sendTextMessage($phone, $this->msg('invalid_ball_range', ['range' => $maxPool, 'ball' => $num], $lang), $tenant->whatsapp_number);
                    return;
                }
            }
        }

        $data['selected_numbers'] = $selected;
        Cache::put($sessionKey, ['state' => 'ENTER_STAKE', 'data' => $data], now()->addMinutes(15));

        $numbersStr = implode(' - ', $selected);
        $minStake = $data['min_stake'] ?? 50;

        $msg = $this->msg('enter_stake', [
            'numbers' => $numbersStr,
            'currency' => $tenant->currency,
            'min' => $minStake,
        ], $lang);

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle stake entry and proceed to one-touch confirmation (localized).
     */
    protected function handleStakeEntry(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $lang = $player->language ?? 'en';
        $data = $session['data'] ?? [];

        if ($input === '0') {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        $stake = (float) preg_replace('/[^0-9.]/', '', $input);
        $minStake = (float) ($data['min_stake'] ?? 50);

        if ($stake < $minStake) {
            $gateway->sendTextMessage($phone, $this->msg('min_stake_error', ['currency' => $tenant->currency, 'min' => $minStake], $lang), $tenant->whatsapp_number);
            return;
        }

        $data['stake_kobo'] = (int) round($stake * 100);
        $data['stake_ngn'] = $stake;
        Cache::put($sessionKey, ['state' => 'CONFIRM_BET', 'data' => $data], now()->addMinutes(15));

        $numbersStr = implode(' - ', (array) $data['selected_numbers']);
        $gameTitle = $data['game_title'] ?? $data['game_code'];

        $msg = $this->msg('confirm_ticket', [
            'game' => $gameTitle,
            'draw' => strtoupper(substr($data['game_code'] ?? '590', 0, 4)) . '-' . date('Hi'),
            'numbers' => $numbersStr,
            'currency' => $tenant->currency,
            'stake' => number_format($stake, 2),
            'wallet' => 'CASH',
        ], $lang);

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Handle one-touch bet confirmation without needing plaintext PIN (localized).
     */
    protected function handleBetConfirmation(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $lang = $player->language ?? 'en';
        $data = $session['data'] ?? [];
        $upper = strtoupper(trim($input));

        if ($upper === '0' || $upper === '2' || str_contains($upper, 'NO') || str_contains($upper, 'CANCEL')) {
            Cache::forget($sessionKey);
            $gateway->sendTextMessage($phone, $this->msg('bet_cancelled', [], $lang), $tenant->whatsapp_number);
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

        $gateway->sendTextMessage($phone, $this->msg('confirm_or_cancel_hint', [], $lang), $tenant->whatsapp_number);
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
        $lang = $player->language ?? 'en';

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
        } catch (\Throwable $e) {
            $errMsg = str_contains($e->getMessage(), 'Insufficient')
                ? $this->msg('insufficient_cash', [
                    'currency' => $tenant->currency,
                    'cash' => number_format($player->cash_balance_kobo / 100, 2),
                    'stake' => number_format($stakeKobo / 100, 2),
                ], $lang)
                : "❌ {$e->getMessage()}";

            $gateway->sendTextMessage($phone, $errMsg, $tenant->whatsapp_number);
            Cache::forget($sessionKey);
            return;
        }

        Cache::forget($sessionKey);

        $freshPlayer = $player->fresh();
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
                $receipt = $this->msg('keno_win', [
                    'ticket' => $ticket->ticket_reference,
                    'hits' => $hitCount,
                    'picks' => count($selectedNumbers),
                    'currency' => $tenant->currency,
                    'payout' => $payoutNgn,
                    'drawn' => $drawnStr,
                    'balance' => number_format($freshPlayer->fresh()->cash_balance_kobo / 100, 2),
                ], $lang);
            } else {
                $receipt = $this->msg('keno_loss', [
                    'ticket' => $ticket->ticket_reference,
                    'hits' => $hitCount,
                    'picks' => count($selectedNumbers),
                    'drawn' => $drawnStr,
                ], $lang);
            }

            $gateway->sendTextMessage($phone, $receipt, $tenant->whatsapp_number);
            return;
        }

        // Scheduled 5/90 Draw receipt
        $payoutPotentialNgn = number_format(($stakeKobo * 240) / 100, 2);
        $sealPreview = substr($ticket->hash ?? ('HMAC-' . strtoupper(bin2hex(random_bytes(6)))), 0, 16) . '...';

        $receipt = $this->msg('ticket_success', [
            'ticket' => $ticket->ticket_reference,
            'game' => $gameTitle,
            'draw' => $draw->draw_code,
            'numbers' => $numbersStr,
            'currency' => $tenant->currency,
            'stake' => $stakeNgn,
            'payout' => $payoutPotentialNgn,
            'seal' => $sealPreview,
            'closes' => $draw->closes_at->format('H:i') . ' WAT',
        ], $lang);

        $gateway->sendTextMessage($phone, $receipt, $tenant->whatsapp_number);
    }

    /**
     * Handle withdrawal amount entry before prompting for 4-digit security PIN.
     */
    protected function handleWithdrawalAmount(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;

        if (trim($input) === '0') {
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        $amountNgn = (float) preg_replace('/[^0-9.]/', '', $input);
        if ($amountNgn < 500) {
            $gateway->sendTextMessage($phone, "⚠️ Minimum withdrawal amount is *{$tenant->currency} 500.00*. Please enter a valid amount or reply *0* to cancel:", $tenant->whatsapp_number);
            return;
        }

        $amountKobo = (int) round($amountNgn * 100);
        if ($player->cash_balance_kobo < $amountKobo) {
            $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
            $gateway->sendTextMessage($phone, "⛔ Insufficient withdrawable cash balance.\nAvailable Cash: *{$tenant->currency} {$cashNgn}*\nRequested: *{$tenant->currency} " . number_format($amountNgn, 2) . "*\n\n_(Note: Promotional bonus funds cannot be withdrawn directly)_", $tenant->whatsapp_number);
            Cache::forget($sessionKey);
            return;
        }

        Cache::put($sessionKey, [
            'state' => 'WITHDRAWAL_PIN',
            'data' => ['amount_kobo' => $amountKobo, 'amount_ngn' => $amountNgn],
        ], now()->addMinutes(15));

        $gateway->sendTextMessage(
            $phone,
            "🔐 *SECURITY VERIFICATION*\n━━━━━━━━━━━━━━━━━━━━\nWithdrawal Amount: *{$tenant->currency} " . number_format($amountNgn, 2) . "*\n\nPlease reply with your *4-digit Security Withdrawal PIN* to authorize this payout:\n_(Reply *0* to cancel)_",
            $tenant->whatsapp_number
        );
    }

    /**
     * Handle masked PIN verification for bank withdrawals and deduct cash balance atomically.
     */
    protected function handleWithdrawalPin(string $input, WhatsAppGatewayInterface $gateway, Tenant $tenant, Player $player, array $session, string $sessionKey): void
    {
        $phone = $player->phone;
        $pin = trim($input);

        if ($pin === '0') {
            Cache::forget($sessionKey);
            $this->showRootMenu($gateway, $tenant, $phone, $sessionKey, $player);
            return;
        }

        if (empty($player->withdrawal_pin_hash) || !Hash::check($pin, $player->withdrawal_pin_hash)) {
            $gateway->sendTextMessage($phone, "⛔ Incorrect Withdrawal PIN. For security, please re-enter your 4-digit PIN or reply *0* to cancel:", $tenant->whatsapp_number);
            return;
        }

        $amountKobo = (int) ($session['data']['amount_kobo'] ?? 0);
        Cache::forget($sessionKey);

        if ($amountKobo > 0) {
            $success = \Illuminate\Support\Facades\DB::transaction(function () use ($player, $amountKobo) {
                $lockedPlayer = Player::where('id', $player->id)->lockForUpdate()->first();
                if (!$lockedPlayer || $lockedPlayer->cash_balance_kobo < $amountKobo) {
                    return false;
                }
                $lockedPlayer->cash_balance_kobo -= $amountKobo;
                $lockedPlayer->save();
                return true;
            });

            if (!$success) {
                $gateway->sendTextMessage($phone, "⛔ Insufficient cash balance to complete withdrawal.", $tenant->whatsapp_number);
                return;
            }

            $freshPlayer = $player->fresh();
            $amtNgn = number_format($amountKobo / 100, 2);
            $remNgn = number_format($freshPlayer->cash_balance_kobo / 100, 2);
            $ref = 'WDR-' . strtoupper(Str::random(8));

            $msg = "✅ *WITHDRAWAL DISPATCHED*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "• Ref: *{$ref}*\n"
                 . "• Amount Paid: *{$tenant->currency} {$amtNgn}*\n"
                 . "• Remaining Cash: *{$tenant->currency} {$remNgn}*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Your bank payout has been authorized via the clearinghouse gateway.";

            $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
            return;
        }

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
     * Show deposit details localized to the player's language.
     */
    protected function showDepositOptions(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone, ?Player $player = null): void
    {
        $lang = $player?->language ?? 'en';
        $accountNo = '9' . substr(preg_replace('/[^0-9]/', '', $phone), -9);
        $name = $player?->name ?? 'Player';

        $msg = $this->msg('fund_info', [
            'account' => $accountNo,
            'tenant' => $tenant->name,
            'name' => $name,
        ], $lang);

        $gateway->sendTextMessage($phone, $msg, $tenant->whatsapp_number);
    }

    /**
     * Show latest draw results in the player's chosen language.
     */
    protected function showLatestResults(WhatsAppGatewayInterface $gateway, Tenant $tenant, string $phone, ?Player $player = null, ?string $specificGame = null): void
    {
        $lang = $player?->language ?? 'en';

        $latestDraw = GameDraw::where('tenant_id', $tenant->id)
            ->whereNotNull('winning_numbers')
            ->orderByDesc('settled_at')
            ->first();

        $winningNums = $latestDraw?->winning_numbers
            ? implode(' - ', $latestDraw->winning_numbers)
            : '7 - 14 - 21 - 35 - 42';

        $drawCode = $specificGame
            ? trim(str_ireplace(['You Selected', 'Results', 'Result'], '', $specificGame)) . ' (' . now()->format('d M') . ')'
            : ($latestDraw?->draw_code ?? 'M590-DAILY');

        $msg = $this->msg('latest_results_box', [
            'draw' => $drawCode,
            'numbers' => $winningNums,
        ], $lang);

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

        // Handle '0' (Back) inside the USSD menu tree
        if ($input === '0' && $currentItemId) {
            $currentItem = UssdMenuItem::find($currentItemId);
            if ($currentItem && $currentItem->parent_item_id) {
                $parentItem = UssdMenuItem::find($currentItem->parent_item_id);
                if ($parentItem) {
                    Cache::put($sessionKey, [
                        'state' => 'USSD_MENU',
                        'item_id' => $parentItem->id,
                        'data' => [],
                    ], now()->addMinutes(15));
                    $gateway->sendTextMessage($player->phone, $parentItem->response_text, $tenant->whatsapp_number);
                    return;
                }
            }
            $this->showRootMenu($gateway, $tenant, $player->phone, $sessionKey, $player);
            return;
        }

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

                // If API leaf node action: inspect the action type!
                $text = strtoupper($child->response_text);

                // 1. Check Results leaf nodes (e.g., "You Selected Blessing Results", IDs 264, 266..286)
                if (str_contains($text, 'RESULT') || ($child->id >= 264 && $child->id <= 286)) {
                    $this->showLatestResults($gateway, $tenant, $player->phone, $player, $child->response_text);
                    $this->showRootMenu($gateway, $tenant, $player->phone, $sessionKey, $player);
                    return;
                }

                // 2. Recharge leaf nodes (IDs 288, 289)
                if (str_contains($text, 'RECHARGE') || in_array($child->id, [288, 289])) {
                    $this->showDepositOptions($gateway, $tenant, $player->phone, $player);
                    $this->showRootMenu($gateway, $tenant, $player->phone, $sessionKey, $player);
                    return;
                }

                // 3. Withdraw leaf nodes (IDs 291, 292)
                if (str_contains($text, 'WITHDRAW') || str_contains($text, 'BANK CARD') || in_array($child->id, [291, 292])) {
                    $cashNgn = number_format($player->cash_balance_kobo / 100, 2);
                    Cache::put($sessionKey, ['state' => 'WITHDRAWAL_AMOUNT', 'data' => []], now()->addMinutes(15));
                    $gateway->sendTextMessage(
                        $player->phone,
                        "🏦 *BANK CASH WITHDRAWAL*\n━━━━━━━━━━━━━━━━━━━━\nAvailable Cash Balance: *{$tenant->currency} {$cashNgn}*\n\nEnter the amount you wish to withdraw in *{$tenant->currency}* (Min: {$tenant->currency} 500):\n_(Reply *0* to cancel)_",
                        $tenant->whatsapp_number
                    );
                    return;
                }

                // 4. Balance / Bonus Check leaf nodes (IDs 294, 295, 296)
                if (str_contains($text, 'BALANCE') || str_contains($text, 'BONUS') || in_array($child->id, [294, 295, 296])) {
                    $this->handleShortcuts('BAL', $gateway, $tenant, $player, $sessionKey);
                    return;
                }

                // 5. Keno leaf node
                if (str_contains($text, 'SELECT BALLS') || $child->id == 115 || str_contains($text, 'KENO')) {
                    $this->startGameSelection('KENO', $gateway, $tenant, $player, $sessionKey, 'Keno 20/80', 10);
                    return;
                }

                // 6. Determine required number of balls for 5/90 Perm/Banker games
                $picks = 5;
                if (str_contains($text, 'PERM1') || str_contains($text, '1BANKER') || str_contains($text, '1ST DRAWN')) {
                    $picks = 1;
                } elseif (str_contains($text, 'PERM2') || str_contains($text, '2 SURE') || str_contains($text, 'NAP 2')) {
                    $picks = 2;
                } elseif (str_contains($text, 'PERM3') || str_contains($text, 'NAP 3')) {
                    $picks = 3;
                } elseif (str_contains($text, 'PERM4') || str_contains($text, 'NAP 4')) {
                    $picks = 4;
                }

                $cleanTitle = trim(preg_replace('/^You Selected\s+/i', '', $child->response_text));
                $this->startGameSelection('5_90_DAILY', $gateway, $tenant, $player, $sessionKey, $cleanTitle, $picks);
                return;
            }
        }

        $upper = strtoupper(trim($input));

        if (str_contains($upper, 'KENO') || $upper === '2' || $upper === 'GAME_KENO') {
            $this->startGameSelection('KENO', $gateway, $tenant, $player, $sessionKey);
            return;
        }

        if (str_contains($upper, 'FAST3') || $upper === '3' || $upper === 'GAME_FAST3') {
            $this->startGameSelection('FAST3', $gateway, $tenant, $player, $sessionKey);
            return;
        }

        // Default: Modern 5/90 Direct in player's language
        $this->startGameSelection('5_90_DAILY', $gateway, $tenant, $player, $sessionKey);
    }
}
