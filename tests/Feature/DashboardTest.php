<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ModernLotterySeeder::class);
        $this->seed(UserSeeder::class);
        $this->adminUser = User::where('email', 'admin@q4iltd.com')->first();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign In to Dashboard');
        $response->assertSee('admin@q4iltd.com');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@q4iltd.com',
            'password' => 'admin123456',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_dashboard_renders_successfully_for_authenticated_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Q4I Financial Engine');
        $response->assertSee('Registered Players');
        $response->assertSee('Live WhatsApp Bets');
        $response->assertSee('Operator Reps and Access');
    }

    public function test_dashboard_stats_api_returns_accurate_splits(): void
    {
        $tenant = Tenant::first();

        $response = $this->actingAs($this->adminUser)->getJson('/api/dashboard/stats?tenant_id=' . $tenant->id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tenant' => ['id', 'name', 'currency'],
            'financials' => ['total_turnover', 'total_tickets', 'gross_gaming_revenue'],
            'splits' => [
                'q4i_percentage',
                'q4i_accrued_earned',
                'q4i_available_to_spend',
                'operator_percentage',
                'operator_accrued_pool',
            ],
            'audit' => ['ledger_reconciled'],
        ]);

        $this->assertEquals(35.00, $response->json('splits.q4i_percentage'));
        $this->assertEquals(65.00, $response->json('splits.operator_percentage'));
    }
}
