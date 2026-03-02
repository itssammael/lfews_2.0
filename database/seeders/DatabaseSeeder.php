<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(LocationTypeSeeder::class);

        $adminRole = Role::where('name', 'admin')->first();

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('1234'),
        ])->roles()->attach($adminRole);
        $this->call(FloodRiskSeeder::class);
        $this->call(ContourSeeder::class);
    }
}
