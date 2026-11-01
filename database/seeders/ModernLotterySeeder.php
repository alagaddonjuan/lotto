<?php

namespace Database\Seeders;

use App\Models\LottoGame;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ModernLotterySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'modern-lottery'],
            [
                'name' => 'Modern Lottery Limited',
                'country_code' => 'NG',
                'currency' => 'NGN',
                'whatsapp_number' => '+14155238886', // Twilio Sandbox or live number
                'whatsapp_driver' => 'twilio',
                'operator_driver' => 'modern_lottery',
                'operator_api_url' => 'https://api.modernlottery.ng/v1',
                'operator_api_key' => 'mock_key',
                'q4i_split_percent' => 35.00,
                'operator_split_percent' => 65.00,
                'is_active' => true,
            ]
        );

        LottoGame::updateOrCreate(
            ['tenant_id' => $tenant->id, 'game_code' => 'KENO_20_80'],
            [
                'name' => 'Keno 20/80',
                'description' => 'Select 10 numbers from 1 to 80. Match numbers to win big cash prizes!',
                'min_stake' => 50.00,
                'max_stake' => 50000.00,
                'total_pool_numbers' => 80,
                'required_picks' => 10,
                'is_active' => true,
            ]
        );

        LottoGame::updateOrCreate(
            ['tenant_id' => $tenant->id, 'game_code' => '5_90_DAILY'],
            [
                'name' => 'Modern 5/90 Direct',
                'description' => 'Classic 5/90 lottery draw. Match 1 to 5 numbers from 1 to 90.',
                'min_stake' => 100.00,
                'max_stake' => 100000.00,
                'total_pool_numbers' => 90,
                'required_picks' => 5,
                'is_active' => true,
            ]
        );
    }
}

