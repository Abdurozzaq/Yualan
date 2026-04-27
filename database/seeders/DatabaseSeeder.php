<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mr. Super Kece',
            'email' => 'sa@example.com',
            'role' => 'superadmin',
            'password' => Hash::make('password'),
            'tenant_id' => null
        ]);

        $this->call([
            SaasSettingsSeeder::class,
            FullTenantSeeder::class
        ]);
    }
}
