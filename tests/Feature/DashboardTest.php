<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\ModernLotterySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ModernLotterySeeder::class);
    }

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Q4I Financial Engine');
        $response->assertSee('35/65 Split Ledger');
        $response->assertSee('Modern Lottery Limited');
    }

    public function test_dashboard_stats_api_returns_accurate_splits(): void
    {
        $tenant = Tenant::first();

        $response = $this->getJson('/api/dashboard/stats?tenant_id=' . $tenant->id);
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

