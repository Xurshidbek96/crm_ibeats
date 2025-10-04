<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'phone1' => '1111111',
            'password' => Hash::make('admin12345'),
        ]);
        $user->assignRole([1]);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin1@gmail.com',
            'phone1' => '222222',
            'password' => Hash::make('admin12345'),
        ]);
        $user->assignRole([2]);
    }
}
