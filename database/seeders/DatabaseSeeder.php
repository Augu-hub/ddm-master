<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Seed utilisateurs de démo (déjà présent)
        User::factory()->create([
            'name' => 'User',
            'email' => 'demo@user.com',
            'password' => Hash::make('password'),
        ]);

        // 2) Appeler le seeder multi-tenants
        $this->call(MultiTenantSeeder::class);
    }
}
