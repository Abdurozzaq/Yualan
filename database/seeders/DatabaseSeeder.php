<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Mr. Super Kece',
            'email' => 'sa@example.com',
            'role' => 'superadmin',
        ]);

        $this->call([
            SaasSettingsSeeder::class,
            FullTenantSeeder::class
        ]);
    }
}
