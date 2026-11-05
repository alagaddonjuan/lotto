<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\ModernLotteryUssdMenuSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthAndPlayerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ModernLotterySeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(ModernLotteryUssdMenuSeeder::class);
        $this->tenant = Tenant::first();
    }

    public function test_new_operator_rep_can_register_via_signed_invitation(): void
    {
        $admin = User::where('email', 'admin@q4iltd.com')->first();

        $this->actingAs($admin);

        // Issue invitation
        $inviteRes = $this->post('/dashboard/invitations', [
            'email' => 'john@operator.ng',
            'role' => 'operator_rep',
            'tenant_id' => $this->tenant->id,
        ]);

        $inviteRes->assertSessionHas('invitation_url');
        $signedUrl = session('invitation_url');

        $this->assertDatabaseHas('operator_invitations', [
            'email' => 'john@operator.ng',
            'role' => 'operator_rep',
            'tenant_id' => $this->tenant->id,
        ]);

        // Accept invitation via signed URL
        $acceptRes = $this->post($signedUrl, [
            'name' => 'John Operator',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $acceptRes->assertRedirect('/two-factor-setup');

        $this->assertDatabaseHas('users', [
            'email' => 'john@operator.ng',
            'role' => 'operator_rep',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_user_with_2fa_is_redirected_to_challenge(): void
    {
        $user = User::create([
            'name' => 'Secured Admin',
            'email' => 'secured@q4iltd.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'two_factor_enabled' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'secured@q4iltd.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        // Solve challenge using the actual generated OTP or test helper
        $actualOtp = $user->fresh()->two_factor_code;
        $verifyResponse = $this->withSession([
            'auth.2fa.id' => $user->id,
            'auth.2fa.remember' => false,
        ])->post('/two-factor-challenge', [
            'code' => $actualOtp,
        ]);

        $verifyResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_and_reset_password_flow(): void
    {
        $user = User::where('email', 'admin@q4iltd.com')->first();

        $forgotResponse = $this->post('/forgot-password', [
            'email' => 'admin@q4iltd.com',
        ]);

        $forgotResponse->assertSessionHas('status');

        $token = 'test-token-secret-123456';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'admin@q4iltd.com'],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetResponse = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'admin@q4iltd.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $resetResponse->assertRedirect('/login');

        // Test login with new password (triggers 2FA challenge for 2FA-secured admin)
        $loginResponse = $this->post('/login', [
            'email' => 'admin@q4iltd.com',
            'password' => 'newpassword123',
        ]);

        $loginResponse->assertRedirect('/two-factor-challenge');
    }

    public function test_player_portal_renders_kiosk(): void
    {
        $response = $this->get('/play');
        $response->assertStatus(200);
        $response->assertSee('Play Kiosk');
        $response->assertSee('Newcomer Registration');
        $response->assertSee('Modern Lottery Bot');
    }

    public function test_player_portal_registers_newcomer_with_trial_balance(): void
    {
        $response = $this->postJson('/play/register', [
            'name' => 'Emeka Johnson',
            'phone' => '08031112233',
            'pin' => '4321',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('players', [
            'phone' => '2348031112233',
            'name' => 'Emeka Johnson',
        ]);
    }

    public function test_player_portal_simulated_chat(): void
    {
        $response = $this->postJson('/play/chat', [
            'phone' => '2348039876543',
            'message' => 'MENU',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('reply'));
    }

    public function test_dva_payment_webhook_credits_player(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'DVA_REF_TEST_' . rand(1000, 9999),
                'amount' => 500000, // ₦5,000 in kobo
                'customer' => [
                    'phone' => '2348039876543',
                    'first_name' => 'Chinedu',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'accepted',
            'amount_kobo' => 500000,
        ]);

        $player = Player::where('phone', '2348039876543')->first();
        $this->assertNotNull($player);
        $this->assertEquals(500000, $player->cash_balance_kobo);
    }

    public function test_two_factor_notification_is_dispatched_and_previewable_in_test_mode(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'OTP Test User',
            'email' => 'otp-test@q4iltd.com',
            'password' => Hash::make('password123'),
            'role' => 'operator_rep',
            'two_factor_enabled' => true,
        ]);

        $res = $this->post('/login', [
            'email' => 'otp-test@q4iltd.com',
            'password' => 'password123',
        ]);

        $res->assertRedirect('/two-factor-challenge');

        // Verify the notification was dispatched
        Notification::assertSentTo(
            $user,
            TwoFactorCodeNotification::class,
            function (TwoFactorCodeNotification $notification) use ($user) {
                return $notification->code === $user->fresh()->two_factor_code;
            }
        );

        // Challenge screen renders cleanly without exposing the 2FA OTP code on screen
        $challengeRes = $this->withSession([
            'auth.2fa.id' => $user->id,
        ])->get('/two-factor-challenge');

        $challengeRes->assertStatus(200);
        $challengeRes->assertDontSee($user->fresh()->two_factor_code);
        $challengeRes->assertDontSee('Quick Fill');
    }

    public function test_auth_manage_artisan_command(): void
    {
        $this->artisan('auth:user')
            ->expectsOutputToContain('Q4I Clearinghouse Operator Directory')
            ->assertSuccessful();

        $this->artisan('auth:user admin@q4iltd.com --disable-2fa')
            ->expectsOutputToContain('2FA has been DISABLED')
            ->assertSuccessful();

        $this->assertFalse(User::where('email', 'admin@q4iltd.com')->value('two_factor_enabled'));

        $this->artisan('auth:user admin@q4iltd.com --enable-2fa')
            ->expectsOutputToContain('2FA has been ENABLED')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'admin@q4iltd.com')->value('two_factor_enabled'));
    }
}
