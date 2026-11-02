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
        $response->assertSee('Sign In to Portal');
        $response->assertSee('admin@q4iltd.com');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@q4iltd.com',
            'password' => 'admin123456',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

    public function test_dashboard_renders_successfully_for_authenticated_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Q4I Financial Engine');
        $response->assertSee('Registered Players');
        $response->assertSee('Transactions');
        $response->assertSee('Hash Seals');
        $response->assertSee('Staff Invitations');
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

    public function test_simulate_bet_api_records_35_65_split(): void
    {
        $tenant = Tenant::first();

        $response = $this->actingAs($this->adminUser)->postJson('/api/dashboard/simulate-bet', [
            'tenant_id' => $tenant->id,
            'phone' => '2348039876543',
            'game_code' => '5_90_DAILY',
            'stake' => 1000,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals(350.00, $response->json('ticket.q4i_share'));
        $this->assertEquals(650.00, $response->json('ticket.operator_share'));
    }

    public function test_settlement_invoice_renders_printable_statement(): void
    {
        $tenant = Tenant::first();

        $response = $this->actingAs($this->adminUser)->get('/dashboard/settlement-invoice?tenant_id=' . $tenant->id);
        $response->assertStatus(200);
        $response->assertSee('Q4I LIMITED');
        $response->assertSee('Official Settlement Statement');
        $response->assertSee('35.00% Fixed');
    }

    public function test_settlement_csv_exports_valid_stream(): void
    {
        $tenant = Tenant::first();

        $response = $this->actingAs($this->adminUser)->get('/dashboard/export-settlement-csv?tenant_id=' . $tenant->id);
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
