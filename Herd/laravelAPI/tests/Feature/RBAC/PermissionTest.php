<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Admin']);
    $this->viewPermissionsPermission = Permission::factory()->create(['slug' => 'permissions.view']);

    $this->adminRole->permissions()->sync([
        $this->viewPermissionsPermission->id,
    ]);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole($this->adminRole);
});

test('admin can list permissions grouped by resource', function () {
    // 1. Setup permissions with specific slug patterns
    Permission::factory()->create(['slug' => 'users.view']);
    Permission::factory()->create(['slug' => 'users.create']);
    Permission::factory()->create(['slug' => 'products.view']);

    // 2. Execute the request
    $response = $this->actingAs($this->adminUser)
        ->getJson('/api/permissions');

    // 3. Verify grouped output
    $response->assertStatus(200);

    // Assert grouped structure exists
    $response->assertJsonStructure(['data' => [['resource', 'label', 'permissions']]]);

    // Check for resources
    $resources = collect($response->json('data'))->pluck('resource');
    $this->assertTrue($resources->contains('users'));
    $this->assertTrue($resources->contains('products'));
    $this->assertTrue($resources->contains('permissions'));

    // Check count inside a group
    $usersGroup = collect($response->json('data'))->firstWhere('resource', 'users');
    $this->assertCount(2, $usersGroup['permissions']);
});

test('unauthorized user cannot list permissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/permissions')
        ->assertStatus(403);
});
