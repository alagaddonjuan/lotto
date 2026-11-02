<?php

namespace Tests\Feature;

use App\Models\BetTicket;
use App\Models\LedgerEntry;
use App\Models\Player;
use App\Models\Tenant;
use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\ModernLotteryUssdMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LottoBotEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ModernLotterySeeder::class);
        $this->seed(ModernLotteryUssdMenuSeeder::class);
        $this->tenant = Tenant::where('slug', 'modern-lottery')->first();

        // Bypass signatures in testing
        config(['services.twilio.bypass_signature' => true]);
    }

    public function test_newcomer_onboarding_and_bet_placement_flow(): void
    {
        $playerPhone = '2348012345678';
        $twilioSender = $this->tenant->whatsapp_number;

        // Step 1: Newcomer sends "HI" -> Welcomed and asked for Name
        $res1 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => 'HI',
            'MessageSid' => 'SM_TEST_001',
        ]);
        $res1->assertStatus(200);

        // Step 2: User provides Name "Michael" -> Asked for 4-digit PIN
        $res2 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => 'Michael',
            'MessageSid' => 'SM_TEST_002',
        ]);
        $res2->assertStatus(200);

        // Step 3: User sets 4-digit PIN "1234" -> Account created, trial wallet credited, Main Menu shown
        $res3 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '1234',
            'MessageSid' => 'SM_TEST_003',
        ]);
        $res3->assertStatus(200);

        // Verify Player record was updated
        $player = Player::where('phone', $playerPhone)->first();
        $this->assertNotNull($player);
        $this->assertEquals('Michael', $player->name);
        $this->assertNotNull($player->pin_hash);

        // Step 4: User selects "1" -> Play Games (Item 113)
        $res4 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '1',
            'MessageSid' => 'SM_TEST_004',
        ]);
        $res4->assertStatus(200);

        // Step 5: User selects "1" -> Keno 20/80 (Item 114)
        $res5 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '1',
            'MessageSid' => 'SM_TEST_005',
        ]);
        $res5->assertStatus(200);

        // Step 6: User selects "1" -> Select Balls (Item 115, API)
        $res6 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '1',
            'MessageSid' => 'SM_TEST_006',
        ]);
        $res6->assertStatus(200);

        // Credit cash balance for real clearinghouse betting
        $player = Player::where('phone', $playerPhone)->first();
        $player->update(['cash_balance_kobo' => 100000]); // ₦1,000.00

        // Step 7: User chooses Quick Pick "QP"
        $res7 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => 'QP',
            'MessageSid' => 'SM_TEST_007',
        ]);
        $res7->assertStatus(200);

        // Step 8: User enters stake "200"
        $res8 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '200',
            'MessageSid' => 'SM_TEST_008',
        ]);
        $res8->assertStatus(200);

        // Step 9: User confirms with "1" (One-touch confirmation, no plaintext PIN)
        $res9 = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '1',
            'MessageSid' => 'SM_TEST_009',
        ]);
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
        $twilioSender = $this->tenant->whatsapp_number;

        // Create player with a name
        Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Blessing',
        ]);

        // Chained command "5*1" -> Directly checks Credit Balance
        $res = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '5*1',
            'MessageSid' => 'SM_DEEP_001',
        ]);
        $res->assertStatus(200);

        // Command "6" -> Directly opens Contact Us
        $resContact = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => '6',
            'MessageSid' => 'SM_DEEP_002',
        ]);
        $resContact->assertStatus(200);
    }

    public function test_fast_line_bet_shortcut(): void
    {
        $playerPhone = '2348077665544';
        $twilioSender = $this->tenant->whatsapp_number;

        // Player with cash balance
        Player::create([
            'tenant_id' => $this->tenant->id,
            'phone' => $playerPhone,
            'name' => 'Fast Player',
            'cash_balance_kobo' => 500000,
        ]);

        // Single-line bet: "KENO QP 300" (Institutional standard: no PIN in chat)
        $fastBetRes = $this->postJson('/api/webhook/twilio', [
            'From' => "whatsapp:+{$playerPhone}",
            'To' => "whatsapp:{$twilioSender}",
            'Body' => 'KENO QP 300',
            'MessageSid' => 'SM_FAST_001',
        ]);
        $fastBetRes->assertStatus(200);

        $ticket = BetTicket::where('tenant_id', $this->tenant->id)
            ->where('stake_amount', 300.00)
            ->first();

        $this->assertNotNull($ticket);
        $this->assertEquals(105.00, $ticket->q4i_share);       // 35% of 300 = 105
        $this->assertEquals(195.00, $ticket->operator_share); // 65% of 300 = 195
    }
}
