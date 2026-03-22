<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Product;

beforeEach(function () {
    $this->viewProductsPermission = Permission::factory()->create(['slug' => 'products.view']);
    $this->createProductsPermission = Permission::factory()->create(['slug' => 'products.create']);
    
    $this->productManagerRole = Role::factory()->create(['slug' => 'product_manager']);
    $this->productManagerRole->permissions()->sync([
        $this->viewProductsPermission->id,
        $this->createProductsPermission->id,
    ]);

    $this->user = User::factory()->create(['status' => 'active']);
    $this->user->assignRole($this->productManagerRole);
});

test('user with products.view can list products', function () {
    Product::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/products');

    $response->assertStatus(200);
});

test('user with products.create can create product', function () {
    $category = \App\Models\Category::factory()->create();
    $payload = [
        'category_id' => $category->id,
        'name' => 'New Product',
        'slug' => 'new-product',
        'sku' => 'NP-001',
        'base_price' => 100,
        'stock_quantity' => 10,
        'status' => 'active',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/products', $payload);

    $response->assertStatus(201);
});

test('user without products.update cannot update product', function () {
    $product = Product::factory()->create();
    $payload = ['name' => 'Updated Name'];

    $response = $this->actingAs($this->user)
        ->putJson("/api/products/{$product->id}", $payload);

    $response->assertStatus(403);
});

test('user without categories.view cannot list categories', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/categories');

    $response->assertStatus(403);
});

test('admin can do everything', function () {
    Permission::factory()->create(['slug' => 'categories.view']);
    $adminRole = Role::factory()->create(['slug' => 'admin']);
    $allPermissions = Permission::all();
    $adminRole->permissions()->sync($allPermissions->pluck('id'));
    
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole($adminRole);

    $response = $this->actingAs($admin)
        ->getJson('/api/categories');

    $response->assertStatus(200);
});

test('user with categories.create can create category', function () {
    $permission = Permission::factory()->create(['slug' => 'categories.create']);
    $this->productManagerRole->permissions()->attach($permission);

    $payload = [
        'name' => 'New Category',
        'slug' => 'new-category',
        'status' => 'active',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/categories', $payload);

    $response->assertStatus(201);
});

test('user with variants.create can create variant', function () {
    $permission = Permission::factory()->create(['slug' => 'variants.create']);
    $this->productManagerRole->permissions()->attach($permission);
    $product = Product::factory()->create();

    $payload = [
        'product_id' => $product->id,
        'name' => 'New Variant',
        'sku' => 'VAR-001',
        'price' => 50,
        'stock_quantity' => 5,
        'status' => 'active',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/variants', $payload);

    $response->assertStatus(201);
});

test('user with categories.update can update category', function () {
    $permission = Permission::factory()->create(['slug' => 'categories.update']);
    $this->productManagerRole->permissions()->attach($permission);
    $category = \App\Models\Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->putJson("/api/categories/{$category->id}", ['name' => 'Updated Category']);

    $response->assertStatus(200);
});

test('user with variants.delete can delete variant', function () {
    $permission = Permission::factory()->create(['slug' => 'variants.delete']);
    $this->productManagerRole->permissions()->attach($permission);
    $variant = \App\Models\ProductVariant::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/variants/{$variant->id}");

    $response->assertStatus(204);
});


test('user with variants.view can list variants', function () {
    $permission = Permission::factory()->create(['slug' => 'variants.view']);
    $this->productManagerRole->permissions()->attach($permission);

    $response = $this->actingAs($this->user)
        ->getJson('/api/variants');

    $response->assertStatus(200);
});

test('user with categories.view can show category', function () {
    $permission = Permission::factory()->create(['slug' => 'categories.view']);
    $this->productManagerRole->permissions()->attach($permission);
    $category = \App\Models\Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/categories/{$category->id}");

    $response->assertStatus(200);
});

test('user with categories.delete can delete category', function () {
    $permission = Permission::factory()->create(['slug' => 'categories.delete']);
    $this->productManagerRole->permissions()->attach($permission);
    $category = \App\Models\Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/categories/{$category->id}");

    $response->assertStatus(204);
});

test('user with variants.view can show variant', function () {
    $permission = Permission::factory()->create(['slug' => 'variants.view']);
    $this->productManagerRole->permissions()->attach($permission);
    $variant = \App\Models\ProductVariant::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/variants/{$variant->id}");

    $response->assertStatus(200);
});

test('user with variants.update can update variant', function () {
    $permission = Permission::factory()->create(['slug' => 'variants.update']);
    $this->productManagerRole->permissions()->attach($permission);
    $variant = \App\Models\ProductVariant::factory()->create();

    $response = $this->actingAs($this->user)
        ->putJson("/api/variants/{$variant->id}", ['name' => 'Updated Variant']);

    $response->assertStatus(200);
});

test('user without products.delete cannot delete product', function () {
    $product = Product::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(403);
});
