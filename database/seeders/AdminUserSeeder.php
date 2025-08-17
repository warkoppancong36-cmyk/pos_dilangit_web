<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $kasirRole = Role::where('name', 'kasir')->first();

        // Create admin user
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@pos.com',
                'phone' => '081234567890',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create manager user
        User::updateOrCreate(
            ['username' => 'manager'],
            [
                'name' => 'Manager Store',
                'username' => 'manager',
                'email' => 'manager@pos.com',
                'phone' => '081234567891',
                'password' => Hash::make('password123'),
                'role_id' => $managerRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create kasir user (main)
        User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'name' => 'Kasir',
                'username' => 'kasir',
                'email' => 'kasir@pos.com',
                'phone' => '081234567892',
                'password' => Hash::make('password123'),
                'role_id' => $kasirRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create kasir user
        User::updateOrCreate(
            ['username' => 'kasir001'],
            [
                'name' => 'Kasir Satu',
                'username' => 'kasir001',
                'email' => 'kasir001@pos.com',
                'phone' => '081234567893',
                'password' => Hash::make('password123'),
                'role_id' => $kasirRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create another kasir user
        User::updateOrCreate(
            ['username' => 'kasir002'],
            [
                'name' => 'Kasir Dua',
                'username' => 'kasir002',
                'email' => 'kasir002@pos.com',
                'phone' => '081234567894',
                'password' => Hash::make('password123'),
                'role_id' => $kasirRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('POS Users created successfully!');
        $this->command->info('Admin: admin / password123');
        $this->command->info('Manager: manager / password123');
        $this->command->info('Kasir: kasir / password123');
        $this->command->info('Kasir 1: kasir001 / password123');
        $this->command->info('Kasir 2: kasir002 / password123');
    }
}
