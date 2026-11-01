<?php

namespace Tests\Feature;

use Database\Seeders\ModernLotterySeeder;
use Database\Seeders\UserSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(ModernLotterySeeder::class);
        $this->seed(UserSeeder::class);

        $admin = User::first();
        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
    }
}
