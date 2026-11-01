<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\ModernLotteryUssdMenuSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_new_operator_rep_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Operator',
            'email' => 'john@operator.ng',
            'phone' => '+2348039999999',
            'tenant_id' => $this->tenant->id,
            'role' => 'operator_rep',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'enable_2fa' => 1,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'john@operator.ng',
            'role' => 'operator_rep',
            'two_factor_enabled' => 1,
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

        // Solve challenge using demo master code '123456'
        $verifyResponse = $this->withSession([
            'auth.2fa.id' => $user->id,
            'auth.2fa.remember' => false,
        ])->post('/two-factor-challenge', [
            'code' => '123456',
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

        $forgotResponse->assertSessionHas('reset_url');

        $resetUrl = session('reset_url');
        parse_str(parse_url($resetUrl, PHP_URL_QUERY), $params);
        $token = basename(parse_url($resetUrl, PHP_URL_PATH));

        $resetResponse = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'admin@q4iltd.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $resetResponse->assertRedirect('/login');

        // Test login with new password
        $loginResponse = $this->post('/login', [
            'email' => 'admin@q4iltd.com',
            'password' => 'newpassword123',
        ]);

        $loginResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
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
            'status' => 'success',
            'amount' => 5000.00,
        ]);
    }
}
