<?php

namespace Tests\Feature;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\Player;
use App\Models\Tenant;
use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\ModernLotteryUssdMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LottoBotEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass signatures in testing for direct Meta Cloud API
        config(['services.meta.bypass_signature' => true]);
        \Illuminate\Support\Facades\Cache::flush();

        $this->seed(ModernLotterySeeder::class);
        $this->seed(ModernLotteryUssdMenuSeeder::class);
        $this->tenant = Tenant::where('slug', 'modern-lottery')->first();
    }

    /**
     * Helper to dispatch inbound WhatsApp webhook events using standard Meta Cloud API format.
     */
    protected function sendMetaMessage(string $fromPhone, string $text, ?string $buttonId = null): TestResponse
    {
        $messageData = [
            'id' => 'wamid.' . uniqid(),
            'from' => $fromPhone,
        ];

        if ($buttonId) {
            $messageData['type'] = 'interactive';
            $messageData['interactive'] = [
                'type' => 'button_reply',
                'button_reply' => [
                    'id' => $buttonId,
                    'title' => $text,
                ],
            ];
        } else {
            $messageData['type'] = 'text';
            $messageData['text'] = ['body' => $text];
        }

        return $this->postJson('/api/webhook/meta', [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_10001',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => $this->tenant->whatsapp_number,
                                    'phone_number_id' => '1000123456789',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Test User'],
                                        'wa_id' => $fromPhone,
                                    ],
                                ],
                                'messages' => [$messageData],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_newcomer_onboarding_and_bet_placement_flow(): void
    {
        $playerPhone = '2348012345678';

        // Step 1: Newcomer sends "HI" -> Welcomed and asked for Name
        $res1 = $this->sendMetaMessage($playerPhone, 'HI');
        $res1->assertStatus(200);

        // Step 2: User provides Name "Michael" -> Asked for 4-digit PIN
        $res2 = $this->sendMetaMessage($playerPhone, 'Michael');
        $res2->assertStatus(200);

        // Step 3: User sets 4-digit PIN "1234" -> Account created, Main Menu shown
        $res3 = $this->sendMetaMessage($playerPhone, '1234');
        $res3->assertStatus(200);

        // Verify Player record was updated
        $player = Player::where('phone', $playerPhone)->first();
        $this->assertNotNull($player);
        $this->assertEquals('Michael', $player->name);
        $this->assertNotNull($player->pin_hash);

        // Step 4: User selects "1" -> Play Games (Item 113)
        $res4 = $this->sendMetaMessage($playerPhone, '1');
        $res4->assertStatus(200);

        // Step 5: User selects "1" -> Keno 20/80 (Item 114)
        $res5 = $this->sendMetaMessage($playerPhone, '1');
        $res5->assertStatus(200);

        // Step 6: User selects "1" -> Select Balls (Item 115, API)
        $res6 = $this->sendMetaMessage($playerPhone, '1');
        $res6->assertStatus(200);

        // Credit cash balance for real clearinghouse betting
        $player = Player::where('phone', $playerPhone)->first();
        $player->update(['cash_balance_kobo' => 100000]); // ₦1,000.00

        // Step 7: User chooses Quick Pick "QP"
        $res7 = $this->sendMetaMessage($playerPhone, 'QP');
        $res7->assertStatus(200);

        // Step 8: User enters stake "200"
        $res8 = $this->sendMetaMessage($playerPhone, '200');
        $res8->assertStatus(200);

        // Step 9: User confirms with "1" (One-touch confirmation, no plaintext PIN)
        $res9 = $this->sendMetaMessage($playerPhone, '1');
        $res9->assertStatus(200);

        // Verify Bet Ticket & Ledger Split (35% Q4I / 65% Modern Lottery)
        $ticket = BetTicket::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($ticket);
        $this->assertContains($ticket->status, ['accepted', 'settled', 'won']);
        $this->assertEquals(200.00, $ticket->stake_amount);
        $this->assertEquals(70.00, $ticket->q4i_share);
        $this->assertEquals(130.00, $ticket->operator_share);

        $ledgerEntries = LedgerEntry::where('tenant_id', $this->tenant->id)->get();
        $this->assertCount(2, $ledgerEntries);
        $this->assertEquals(70.00, $ledgerEntries[0]->amount);
        $this->assertEquals(130.00, $ledgerEntries[1]->amount);
    }

    public function test_deep_dial_shortcuts_for_registered_player(): void
    {
        $playerPhone = '2348099887766';

        // Create player with a name
        Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Blessing',
        ]);

        // Chained command "5*1" -> Directly checks Credit Balance
        $res = $this->sendMetaMessage($playerPhone, '5*1');
        $res->assertStatus(200);

        // Command "6" -> Directly opens Contact Us
        $resContact = $this->sendMetaMessage($playerPhone, '6');
        $resContact->assertStatus(200);
    }

    public function test_fast_line_bet_shortcut(): void
    {
        $playerPhone = '2348077665544';

        // Player with cash balance
        Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Fast Player',
            'cash_balance_kobo' => 500000,
        ]);

        // Single-line bet: "KENO QP 300" (Institutional standard: no PIN in chat)
        $fastBetRes = $this->sendMetaMessage($playerPhone, 'KENO QP 300');
        $fastBetRes->assertStatus(200);

        $ticket = BetTicket::where('tenant_id', $this->tenant->id)
            ->where('stake_amount', 300.00)
            ->first();

        $this->assertNotNull($ticket);
        $this->assertEquals(105.00, $ticket->q4i_share);       // 35% of 300 = 105
        $this->assertEquals(195.00, $ticket->operator_share); // 65% of 300 = 195
    }

    public function test_nigerian_language_selection_and_pidgin_support(): void
    {
        $playerPhone = '2348033221100';

        $player = Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Chidi',
            'language' => 'en',
        ]);

        // Switch language to Nigerian Pidgin
        $res = $this->sendMetaMessage($playerPhone, 'LANG PCM');
        $res->assertStatus(200);

        $player->refresh();
        $this->assertEquals('pcm', $player->language);

        // Switch to Yoruba
        $resYo = $this->sendMetaMessage($playerPhone, 'LANG YO');
        $resYo->assertStatus(200);

        $player->refresh();
        $this->assertEquals('yo', $player->language);
    }

    public function test_whatsapp_cash_withdrawal_with_pin_verification(): void
    {
        $playerPhone = '2348066554433';

        $player = Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Adebayo',
            'cash_balance_kobo' => 500000, // ₦5,000.00
            'withdrawal_pin_hash' => \Illuminate\Support\Facades\Hash::make('5829'),
        ]);

        // Step 1: Request withdrawal of ₦2,000
        $res1 = $this->sendMetaMessage($playerPhone, 'WITHDRAW 2000');
        $res1->assertStatus(200);

        // Step 2: Authorize with 4-digit PIN "5829"
        $res2 = $this->sendMetaMessage($playerPhone, '5829');
        $res2->assertStatus(200);

        $player->refresh();
        $this->assertEquals(300000, $player->cash_balance_kobo, '₦2,000 (200,000 Kobo) deducted after PIN verification');
    }

    public function test_end_to_end_yoruba_localization_and_meta_character_limits(): void
    {
        // Verify Meta Cloud API character limits across all 5 supported languages
        foreach (['en', 'pcm', 'yo', 'ha', 'ig'] as $locale) {
            $sectionTitle = trans('bot.games_section_title', [], $locale);
            $menuBtn = trans('bot.games_menu_btn', [], $locale);
            $btnPlay = '🎯 ' . trans('bot.btn_play', [], $locale);
            $btnFund = '💳 ' . trans('bot.btn_fund', [], $locale);
            $btnResults = '🏆 ' . trans('bot.btn_results', [], $locale);

            $this->assertLessThanOrEqual(24, mb_strlen($sectionTitle), "games_section_title in [{$locale}] exceeds 24 chars");
            $this->assertLessThanOrEqual(20, mb_strlen($menuBtn), "games_menu_btn in [{$locale}] exceeds 20 chars");
            $this->assertLessThanOrEqual(20, mb_strlen($btnPlay), "btn_play in [{$locale}] exceeds 20 chars");
            $this->assertLessThanOrEqual(20, mb_strlen($btnFund), "btn_fund in [{$locale}] exceeds 20 chars");
            $this->assertLessThanOrEqual(20, mb_strlen($btnResults), "btn_results in [{$locale}] exceeds 20 chars");
        }

        // End-to-end flow in Yoruba
        $playerPhone = '2347034616262';
        Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Michael',
            'language' => 'yo',
            'cash_balance_kobo' => 0,
        ]);

        // Tap Play Game button
        $this->sendMetaMessage($playerPhone, 'Play Game', 'opt_play')->assertStatus(200);

        // Select Modern 5/90 Direct from list
        $this->sendMetaMessage($playerPhone, 'Modern 5/90 Direct', 'game_590')->assertStatus(200);
        $session = \Illuminate\Support\Facades\Cache::get("wa:session:{$playerPhone}");
        $this->assertEquals('SELECT_NUMBERS', $session['state']);

        // Pick 5 numbers -> moves to ENTER_STAKE
        $this->sendMetaMessage($playerPhone, '66 16 65 36 88')->assertStatus(200);
        $session = \Illuminate\Support\Facades\Cache::get("wa:session:{$playerPhone}");
        $this->assertEquals('ENTER_STAKE', $session['state']);

        // Enter stake 200 -> moves to CONFIRM_BET
        $this->sendMetaMessage($playerPhone, '200')->assertStatus(200);
        $session = \Illuminate\Support\Facades\Cache::get("wa:session:{$playerPhone}");
        $this->assertEquals('CONFIRM_BET', $session['state']);
    }

    public function test_ussd_check_results_leaf_node_displays_results_instead_of_number_picker(): void
    {
        $playerPhone = '2348055443322';
        Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Test Player',
            'language' => 'en',
        ]);

        // Navigate USSD: 2 (Check Result) -> 2 (Modern BILLIONAIRE) -> 6 (Blessing Results)
        $this->sendMetaMessage($playerPhone, '2')->assertStatus(200);
        $s1 = \Illuminate\Support\Facades\Cache::get("wa:session:{$playerPhone}");

        $this->sendMetaMessage($playerPhone, '2')->assertStatus(200);
        $s2 = \Illuminate\Support\Facades\Cache::get("wa:session:{$playerPhone}");

        $this->sendMetaMessage($playerPhone, '6')->assertStatus(200);
        $s3 = \Illuminate\Support\Facades\Cache::get("wa:session:{$playerPhone}");

        $count = \App\Models\UssdMenuItem::count();
        $this->assertEquals('ROOT_MENU', $s3['state'] ?? null, "Count: {$count} | S1: " . json_encode($s1) . " | S2: " . json_encode($s2) . " | S3: " . json_encode($s3));
    }
}
