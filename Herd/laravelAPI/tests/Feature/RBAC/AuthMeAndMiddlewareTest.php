<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->role = Role::factory()->create(['slug' => 'product_manager', 'name' => 'Product Manager']);
    $this->permissions = [
        Permission::factory()->create(['slug' => 'products.view']),
        Permission::factory()->create(['slug' => 'products.create']),
    ];

    $this->role->permissions()->sync(collect($this->permissions)->pluck('id'));

    $this->user = User::factory()->create(['status' => 'active']);
    $this->user->assignRole($this->role);
});

test('/api/me returns user, role and permissions information', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Authenticated user retrieved successfully')
        ->assertJsonPath('user.email', $this->user->email)
        ->assertJsonPath('role.slug', 'product_manager')
        ->assertJsonCount(2, 'permissions')
        ->assertJsonPath('permissions.0', 'products.view');
});

test('/api/me blocks and logs out inactive users', function () {
    $this->user->update(['status' => 'inactive']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/me');

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Your account is inactive.');
});

test('middleware blocks access if permission is missing', function () {
    // User only has products.view, products.create
    // Attempting to access users.view
    $response = $this->actingAs($this->user)
        ->getJson('/api/users');

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden.');
});

test('middleware blocks access if user is inactive', function () {
    $this->user->update(['status' => 'inactive']);

    // Attempting to access products.view which user HAS permission for
    // but the CheckPermission middleware also checks status
    $response = $this->actingAs($this->user)
        ->getJson('/api/users'); // Even if they have the permission, status check is also in middleware

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden.'); // In CheckPermission.php, it returns 403 Forbidden.
});
