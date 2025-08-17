<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to all features and settings',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                    'products.view', 'products.create', 'products.edit', 'products.delete',
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'transactions.view', 'transactions.create', 'transactions.edit', 'transactions.delete',
                    'reports.view', 'reports.export',
                    'settings.view', 'settings.edit',
                    'logs.view',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manage store operations and view reports',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit',
                    'products.view', 'products.create', 'products.edit', 'products.delete',
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'transactions.view', 'transactions.create', 'transactions.edit',
                    'reports.view', 'reports.export',
                    'logs.view',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'kasir',
                'display_name' => 'Kasir',
                'description' => 'Handle transactions and basic product management',
                'permissions' => [
                    'products.view',
                    'categories.view',
                    'transactions.view', 'transactions.create',
                    'reports.view',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Supervisor',
                'description' => 'Supervise kasir and basic management tasks',
                'permissions' => [
                    'users.view',
                    'products.view', 'products.create', 'products.edit',
                    'categories.view', 'categories.create', 'categories.edit',
                    'transactions.view', 'transactions.create', 'transactions.edit',
                    'reports.view',
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        $this->command->info('Roles created successfully!');
        $this->command->info('- Admin: Full access');
        $this->command->info('- Manager: Store management');
        $this->command->info('- Supervisor: Basic management');
        $this->command->info('- Kasir: Transaction handling');
    }
}
