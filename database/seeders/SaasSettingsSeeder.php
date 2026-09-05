<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SaasSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('saas_settings')->insert([
            [
                'id' => 1,
                'key' => 'ipaymu_va',
                'value' => '0000009603363136',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'key' => 'ipaymu_api_key',
                'value' => 'SANDBOX6DDB1BC3-3127-46D8-B364-989084082071',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'key' => 'trial_days',
                'value' => '7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
