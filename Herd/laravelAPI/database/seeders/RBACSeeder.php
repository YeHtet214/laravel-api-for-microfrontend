<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RBACSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            // User management
            ['name' => 'View Users', 'slug' => 'users.view'],
            ['name' => 'Create User', 'slug' => 'users.create'],
            ['name' => 'Update User', 'slug' => 'users.update'],
            ['name' => 'Update User Status', 'slug' => 'users.status.update'],
            
            // Role management
            ['name' => 'View Roles', 'slug' => 'roles.view'],
            ['name' => 'Create Role', 'slug' => 'roles.create'],
            ['name' => 'Update Role', 'slug' => 'roles.update'],
            ['name' => 'Delete Role', 'slug' => 'roles.delete'],

            // Permission viewing
            ['name' => 'View Permissions', 'slug' => 'permissions.view'],

            // Portal access
            ['name' => 'Access Portal', 'slug' => 'portal.access'],

            // Product management
            ['name' => 'View Products', 'slug' => 'products.view'],
            ['name' => 'Create Product', 'slug' => 'products.create'],
            ['name' => 'Update Product', 'slug' => 'products.update'],
            ['name' => 'Delete Product', 'slug' => 'products.delete'],
            
            // Inventory management
            ['name' => 'View Inventory', 'slug' => 'inventory.view'],
            ['name' => 'Stock In', 'slug' => 'inventory.stock_in'],
            ['name' => 'Stock Out', 'slug' => 'inventory.stock_out'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        // 2. Create Roles
        $adminRole = Role::updateOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'slug' => 'admin']);
        $productManagerRole = Role::updateOrCreate(['slug' => 'product_manager'], ['name' => 'Product Manager', 'slug' => 'product_manager']);
        $inventoryManagerRole = Role::updateOrCreate(['slug' => 'inventory_manager'], ['name' => 'Inventory Manager', 'slug' => 'inventory_manager']);
        $staffRole = Role::updateOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'slug' => 'staff']);

        // 3. Assign Permissions to Roles
        
        // Admin: ALL permissions
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));

        // Product Manager
        $productManagerPermissions = Permission::whereIn('slug', [
            'portal.access',
            'permissions.view',
            'products.view',
            'products.create',
            'products.update',
        ])->get();
        $productManagerRole->permissions()->sync($productManagerPermissions->pluck('id'));

        // Inventory Manager
        $inventoryManagerPermissions = Permission::whereIn('slug', [
            'portal.access',
            'permissions.view',
            'inventory.view',
            'inventory.stock_in',
            'inventory.stock_out',
        ])->get();
        $inventoryManagerRole->permissions()->sync($inventoryManagerPermissions->pluck('id'));

        // Staff
        $staffPermissions = Permission::whereIn('slug', [
            'portal.access',
        ])->get();
        $staffRole->permissions()->sync($staffPermissions->pluck('id'));

        // 4. Assign Admin role to the first user
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole($adminRole->id);
        }
    }
}
