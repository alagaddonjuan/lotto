<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'modern-lottery')->first();

        // 1. Q4I Super Admin (Platform Owner)
        User::updateOrCreate(
            ['email' => 'admin@q4iltd.com'],
            [
                'name' => 'Michael Alaga (Q4I Admin)',
                'password' => Hash::make('admin123456'),
                'role' => 'super_admin',
                'tenant_id' => null,
            ]
        );

        // 2. Modern Lottery Operator Representative
        if ($tenant) {
            User::updateOrCreate(
                ['email' => 'rep@modernlottery.ng'],
                [
                    'name' => 'Modern Lottery Operations',
                    'password' => Hash::make('rep123456'),
                    'role' => 'operator_rep',
                    'tenant_id' => $tenant->id,
                ]
            );
        }
    }
}

