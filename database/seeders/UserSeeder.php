<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@omniscient.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@omniscient.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Business Owner
        User::create([
            'name' => 'Yohel Genius',
            'email' => 'dprestb@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Pending Owner
        // User::create([
        //     'name' => 'Jane Smith',
        //     'email' => 'pending@omniscient.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'owner',
        //     'status' => 'pending',
        //     'email_verified_at' => now(),
        // ]);
    }
}