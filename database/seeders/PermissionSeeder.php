<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['name' => 'user.view', 'display_name' => 'View Users', 'module' => 'user', 'action' => 'view', 'description' => 'View users'],
            ['name' => 'user.create', 'display_name' => 'Create Users', 'module' => 'user', 'action' => 'create', 'description' => 'Create new users'],
            ['name' => 'user.update', 'display_name' => 'Update Users', 'module' => 'user', 'action' => 'update', 'description' => 'Update users'],
            ['name' => 'user.delete', 'display_name' => 'Delete Users', 'module' => 'user', 'action' => 'delete', 'description' => 'Delete users'],

            // Role Management
            ['name' => 'role.view', 'display_name' => 'View Roles', 'module' => 'role', 'action' => 'view', 'description' => 'View roles'],
            ['name' => 'role.create', 'display_name' => 'Create Roles', 'module' => 'role', 'action' => 'create', 'description' => 'Create new roles'],
            ['name' => 'role.update', 'display_name' => 'Update Roles', 'module' => 'role', 'action' => 'update', 'description' => 'Update roles'],
            ['name' => 'role.delete', 'display_name' => 'Delete Roles', 'module' => 'role', 'action' => 'delete', 'description' => 'Delete roles'],

            // Permission Management
            ['name' => 'permission.view', 'display_name' => 'View Permissions', 'module' => 'permission', 'action' => 'view', 'description' => 'View permissions'],
            ['name' => 'permission.create', 'display_name' => 'Create Permissions', 'module' => 'permission', 'action' => 'create', 'description' => 'Create new permissions'],
            ['name' => 'permission.update', 'display_name' => 'Update Permissions', 'module' => 'permission', 'action' => 'update', 'description' => 'Update permissions'],
            ['name' => 'permission.delete', 'display_name' => 'Delete Permissions', 'module' => 'permission', 'action' => 'delete', 'description' => 'Delete permissions'],
            ['name' => 'permission.assign', 'display_name' => 'Assign Permissions', 'module' => 'permission', 'action' => 'assign', 'description' => 'Assign permissions to users/roles'],

            // Asset Management
            ['name' => 'asset.view', 'display_name' => 'View Assets', 'module' => 'asset', 'action' => 'view', 'description' => 'View assets'],
            ['name' => 'asset.create', 'display_name' => 'Upload Assets', 'module' => 'asset', 'action' => 'create', 'description' => 'Upload new assets'],
            ['name' => 'asset.update', 'display_name' => 'Update Assets', 'module' => 'asset', 'action' => 'update', 'description' => 'Update assets'],
            ['name' => 'asset.delete', 'display_name' => 'Delete Assets', 'module' => 'asset', 'action' => 'delete', 'description' => 'Delete assets'],
            ['name' => 'asset.download', 'display_name' => 'Download Assets', 'module' => 'asset', 'action' => 'download', 'description' => 'Download assets'],

            // Product Management
            ['name' => 'product.view', 'display_name' => 'View Products', 'module' => 'product', 'action' => 'view', 'description' => 'View products'],
            ['name' => 'product.create', 'display_name' => 'Create Products', 'module' => 'product', 'action' => 'create', 'description' => 'Create new products'],
            ['name' => 'product.update', 'display_name' => 'Update Products', 'module' => 'product', 'action' => 'update', 'description' => 'Update products'],
            ['name' => 'product.delete', 'display_name' => 'Delete Products', 'module' => 'product', 'action' => 'delete', 'description' => 'Delete products'],

            // Category Management
            ['name' => 'category.view', 'display_name' => 'View Categories', 'module' => 'category', 'action' => 'view', 'description' => 'View categories'],
            ['name' => 'category.create', 'display_name' => 'Create Categories', 'module' => 'category', 'action' => 'create', 'description' => 'Create new categories'],
            ['name' => 'category.update', 'display_name' => 'Update Categories', 'module' => 'category', 'action' => 'update', 'description' => 'Update categories'],
            ['name' => 'category.delete', 'display_name' => 'Delete Categories', 'module' => 'category', 'action' => 'delete', 'description' => 'Delete categories'],

            // Supplier Management
            ['name' => 'supplier.view', 'display_name' => 'View Suppliers', 'module' => 'supplier', 'action' => 'view', 'description' => 'View suppliers'],
            ['name' => 'supplier.create', 'display_name' => 'Create Suppliers', 'module' => 'supplier', 'action' => 'create', 'description' => 'Create new suppliers'],
            ['name' => 'supplier.update', 'display_name' => 'Update Suppliers', 'module' => 'supplier', 'action' => 'update', 'description' => 'Update suppliers'],
            ['name' => 'supplier.delete', 'display_name' => 'Delete Suppliers', 'module' => 'supplier', 'action' => 'delete', 'description' => 'Delete suppliers'],

            // Customer Management
            ['name' => 'customer.view', 'display_name' => 'View Customers', 'module' => 'customer', 'action' => 'view', 'description' => 'View customers'],
            ['name' => 'customer.create', 'display_name' => 'Create Customers', 'module' => 'customer', 'action' => 'create', 'description' => 'Create new customers'],
            ['name' => 'customer.update', 'display_name' => 'Update Customers', 'module' => 'customer', 'action' => 'update', 'description' => 'Update customers'],
            ['name' => 'customer.delete', 'display_name' => 'Delete Customers', 'module' => 'customer', 'action' => 'delete', 'description' => 'Delete customers'],

            // Purchase Management
            ['name' => 'purchase.view', 'display_name' => 'View Purchases', 'module' => 'purchase', 'action' => 'view', 'description' => 'View purchases'],
            ['name' => 'purchase.create', 'display_name' => 'Create Purchases', 'module' => 'purchase', 'action' => 'create', 'description' => 'Create new purchases'],
            ['name' => 'purchase.update', 'display_name' => 'Update Purchases', 'module' => 'purchase', 'action' => 'update', 'description' => 'Update purchases'],
            ['name' => 'purchase.delete', 'display_name' => 'Delete Purchases', 'module' => 'purchase', 'action' => 'delete', 'description' => 'Delete purchases'],
            ['name' => 'purchase.receive', 'display_name' => 'Receive Purchases', 'module' => 'purchase', 'action' => 'receive', 'description' => 'Receive purchase items'],

            // Inventory Management
            ['name' => 'inventory.view', 'display_name' => 'View Inventory', 'module' => 'inventory', 'action' => 'view', 'description' => 'View inventory'],
            ['name' => 'inventory.update', 'display_name' => 'Update Inventory', 'module' => 'inventory', 'action' => 'update', 'description' => 'Update inventory stock'],
            ['name' => 'inventory.movement', 'display_name' => 'Record Movements', 'module' => 'inventory', 'action' => 'movement', 'description' => 'Record inventory movements'],

            // POS Management
            ['name' => 'pos.view', 'display_name' => 'Access POS', 'module' => 'pos', 'action' => 'view', 'description' => 'Access POS system'],
            ['name' => 'pos.create_order', 'display_name' => 'Create Orders', 'module' => 'pos', 'action' => 'create_order', 'description' => 'Create new orders'],
            ['name' => 'pos.process_payment', 'display_name' => 'Process Payments', 'module' => 'pos', 'action' => 'process_payment', 'description' => 'Process payments'],
            ['name' => 'pos.cancel_order', 'display_name' => 'Cancel Orders', 'module' => 'pos', 'action' => 'cancel_order', 'description' => 'Cancel orders'],
            ['name' => 'pos.refund', 'display_name' => 'Process Refunds', 'module' => 'pos', 'action' => 'refund', 'description' => 'Process refunds'],

            // Cash Management
            ['name' => 'cash.view', 'display_name' => 'View Cash Drawer', 'module' => 'cash', 'action' => 'view', 'description' => 'View cash drawer'],
            ['name' => 'cash.in', 'display_name' => 'Cash In', 'module' => 'cash', 'action' => 'in', 'description' => 'Add cash to drawer'],
            ['name' => 'cash.out', 'display_name' => 'Cash Out', 'module' => 'cash', 'action' => 'out', 'description' => 'Remove cash from drawer'],

            // Discount Management
            ['name' => 'discount.view', 'display_name' => 'View Discounts', 'module' => 'discount', 'action' => 'view', 'description' => 'View discounts'],
            ['name' => 'discount.create', 'display_name' => 'Create Discounts', 'module' => 'discount', 'action' => 'create', 'description' => 'Create new discounts'],
            ['name' => 'discount.update', 'display_name' => 'Update Discounts', 'module' => 'discount', 'action' => 'update', 'description' => 'Update discounts'],
            ['name' => 'discount.delete', 'display_name' => 'Delete Discounts', 'module' => 'discount', 'action' => 'delete', 'description' => 'Delete discounts'],

            // Promotion Management
            ['name' => 'promotion.view', 'display_name' => 'View Promotions', 'module' => 'promotion', 'action' => 'view', 'description' => 'View promotions'],
            ['name' => 'promotion.create', 'display_name' => 'Create Promotions', 'module' => 'promotion', 'action' => 'create', 'description' => 'Create new promotions'],
            ['name' => 'promotion.update', 'display_name' => 'Update Promotions', 'module' => 'promotion', 'action' => 'update', 'description' => 'Update promotions'],
            ['name' => 'promotion.delete', 'display_name' => 'Delete Promotions', 'module' => 'promotion', 'action' => 'delete', 'description' => 'Delete promotions'],

            // Reports
            ['name' => 'report.sales', 'display_name' => 'Sales Reports', 'module' => 'report', 'action' => 'sales', 'description' => 'View sales reports'],
            ['name' => 'report.inventory', 'display_name' => 'Inventory Reports', 'module' => 'report', 'action' => 'inventory', 'description' => 'View inventory reports'],
            ['name' => 'report.financial', 'display_name' => 'Financial Reports', 'module' => 'report', 'action' => 'financial', 'description' => 'View financial reports'],
            ['name' => 'report.export', 'display_name' => 'Export Reports', 'module' => 'report', 'action' => 'export', 'description' => 'Export reports'],

            // System Logs
            ['name' => 'log.view', 'display_name' => 'View Logs', 'module' => 'log', 'action' => 'view', 'description' => 'View system logs'],
            ['name' => 'log.export', 'display_name' => 'Export Logs', 'module' => 'log', 'action' => 'export', 'description' => 'Export logs'],

            // HPP Management
            ['name' => 'hpp.view', 'display_name' => 'View HPP', 'module' => 'hpp', 'action' => 'view', 'description' => 'View HPP calculations'],
            ['name' => 'hpp.update', 'display_name' => 'Update HPP', 'module' => 'hpp', 'action' => 'update', 'description' => 'Update HPP values'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Create admin role and assign all permissions
        $adminRole = Role::firstOrCreate([
            'name' => 'Super Admin'
        ], [
            'display_name' => 'Super Administrator',
            'description' => 'Has access to all system features',
            'is_active' => true
        ]);

        // Assign all permissions to admin role
        $allPermissions = Permission::all();
        $permissionIds = $allPermissions->pluck('id_permission')->toArray();
        $adminRole->permissions()->sync($permissionIds);

        $this->command->info('Permissions seeded successfully!');
    }
}
