<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Admin']);
    $this->viewRolesPermission = Permission::factory()->create(['slug' => 'roles.view']);
    $this->createRolesPermission = Permission::factory()->create(['slug' => 'roles.create']);
    $this->updateRolesPermission = Permission::factory()->create(['slug' => 'roles.update']);
    $this->deleteRolesPermission = Permission::factory()->create(['slug' => 'roles.delete']);

    $this->adminRole->permissions()->sync([
        $this->viewRolesPermission->id,
        $this->createRolesPermission->id,
        $this->updateRolesPermission->id,
        $this->deleteRolesPermission->id,
    ]);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);
});

test('admin can list roles', function () {
    Role::factory()->count(3)->create();

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/roles');

    $response->assertStatus(200)
        ->assertJsonCount(4, 'data'); // 3 + 1 admin role
});

test('admin can search roles', function () {
    Role::factory()->create(['name' => 'Specific Role', 'slug' => 'specific-role']);

    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/roles?search=Specific');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Specific Role');
});

test('admin can get roles for dropdown', function () {
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/roles?dropdown=1');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);
});

test('admin can view role details', function () {
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();
    $role->permissions()->attach($permission);

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/roles/{$role->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $role->id)
        ->assertJsonCount(1, 'data.permissions');
});

test('admin can create role', function () {
    $permissions = Permission::factory()->count(2)->create();
    $payload = [
        'name' => 'New Role',
        'slug' => 'new-role',
        'permission_ids' => $permissions->pluck('id')->toArray(),
    ];

    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/roles', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New Role')
        ->assertJsonCount(2, 'data.permissions');

    $this->assertDatabaseHas('roles', ['slug' => 'new-role']);
});

test('admin can update role', function () {
    $role = Role::factory()->create();
    $newPermissions = Permission::factory()->count(3)->create();
    $payload = [
        'name' => 'Updated Role',
        'slug' => 'updated-role',
        'permission_ids' => $newPermissions->pluck('id')->toArray(),
    ];

    $response = $this->actingAs($this->adminUser)
        ->putJson("/api/roles/{$role->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Role')
        ->assertJsonCount(3, 'data.permissions');
});

test('admin cannot delete role with assigned users', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/roles/{$role->id}");

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Cannot delete role that is currently assigned to users.');
});

test('admin cannot delete business-critical roles', function () {
    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/roles/{$this->adminRole->id}");

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Cannot delete business-critical roles.');
});

test('admin can delete unused role', function () {
    $role = Role::factory()->create();

    $response = $this->actingAs($this->adminUser)
        ->deleteJson("/api/roles/{$role->id}");

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Role deleted successfully.');

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('unauthorized user cannot access role endpoints', function () {
    $user = User::factory()->create(); // No permissions

    $this->actingAs($user)->getJson('/api/roles')->assertStatus(403);
    $this->actingAs($user)->postJson('/api/roles', [])->assertStatus(403);
});
