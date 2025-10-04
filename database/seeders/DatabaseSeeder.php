<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            BalanceSeeder::class,
        ]);

        $users = \App\Models\User::factory(10)->create();
        foreach ($users as $user)
            $user->assignRole([2]);

        \App\Models\Client::factory(20)->create();
        \App\Models\Device::factory(20)->create();
        \App\Models\Investor::factory(5)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
