<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Admin']);
    $this->userManagementPermissions = [
        Permission::factory()->create(['slug' => 'users.view']),
        Permission::factory()->create(['slug' => 'users.create']),
        Permission::factory()->create(['slug' => 'users.update']),
        Permission::factory()->create(['slug' => 'users.status.update']),
    ];

    $this->adminRole->permissions()->sync(collect($this->userManagementPermissions)->pluck('id'));

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);
});

test('admin can list users with role information', function () {
    $staffRole = Role::factory()->create(['slug' => 'staff', 'name' => 'Staff']);
    $user = User::factory()->create();
    $user->assignRole($staffRole);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonStructure(['users' => ['data' => [['id', 'name', 'email', 'role']]]])
        ->assertJsonPath('users.data.1.role.slug', 'staff'); // 0 is adminUser, 1 is the new user
});

test('admin can view user details with role details', function () {
    $role = Role::factory()->create(['slug' => 'product_manager', 'name' => 'Product Manager']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.role.slug', 'product_manager');
});

test('admin can create user with role_id', function () {
    $role = Role::factory()->create(['slug' => 'inventory_manager', 'name' => 'Inventory Manager']);
    $payload = [
        'name' => 'John Inventory',
        'email' => 'john@inventory.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'status' => 'active',
        'role_id' => $role->id,
    ];

    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/users', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('user.name', 'John Inventory')
        ->assertJsonPath('user.role.slug', 'inventory_manager');

    $this->assertDatabaseHas('users', ['email' => 'john@inventory.com']);
    $user = User::where('email', 'john@inventory.com')->first();
    $this->assertTrue($user->hasRole('inventory_manager'));
});

test('admin can update user and change role_id', function () {
    $user = User::factory()->create();
    $oldRole = Role::factory()->create(['slug' => 'staff-member']);
    $user->assignRole($oldRole);

    $newRole = Role::factory()->create(['slug' => 'manager']);
    $payload = [
        'name' => 'Updated User Name',
        'role_id' => $newRole->id,
    ];

    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/users/{$user->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('user.name', 'Updated User Name')
        ->assertJsonPath('user.role.slug', 'manager');

    $this->assertTrue($user->fresh()->hasRole('manager'));
    $this->assertCount(1, $user->fresh()->roles); // Enforces single role logic
});

test('admin can update user status', function () {
    $user = User::factory()->create(['status' => 'active']);

    $response = $this->actingAs($this->adminUser)
        ->patchJson("/api/users/{$user->id}/status", ['status' => 'inactive']);

    $response->assertStatus(200)
        ->assertJsonPath('user.status', 'inactive');

    $this->assertEquals('inactive', $user->fresh()->status);
});

test('creating user requires valid role_id', function () {
    $payload = [
        'name' => 'Error User',
        'email' => 'error@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'status' => 'active',
        'role_id' => 999, // Non-existent role id
    ];

    $this->actingAs($this->adminUser)
        ->postJson('/api/users', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role_id']);
});
