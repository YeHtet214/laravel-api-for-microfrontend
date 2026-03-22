<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Permission\PermissionController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductVariantController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'getAuthUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create');
        Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
        Route::patch('/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.status.update');
    });

    // Role Management
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::get('/{role}', [RoleController::class, 'show'])->middleware('permission:roles.view');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create');
        Route::put('/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');
    });

    // Permission Read API
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view');

    // Product Feature Routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->middleware('permission:categories.view');
        Route::get('/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view');
        Route::post('/', [CategoryController::class, 'store'])->middleware('permission:categories.create');
        Route::put('/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete');
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->middleware('permission:products.view');
        Route::get('/{product}', [ProductController::class, 'show'])->middleware('permission:products.view');
        Route::post('/', [ProductController::class, 'store'])->middleware('permission:products.create');
        Route::put('/{product}', [ProductController::class, 'update'])->middleware('permission:products.update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');
    });

    Route::prefix('variants')->group(function () {
        Route::get('/', [ProductVariantController::class, 'index'])->middleware('permission:variants.view');
        Route::get('/{variant}', [ProductVariantController::class, 'show'])->middleware('permission:variants.view');
        Route::post('/', [ProductVariantController::class, 'store'])->middleware('permission:variants.create');
        Route::put('/{variant}', [ProductVariantController::class, 'update'])->middleware('permission:variants.update');
        Route::delete('/{variant}', [ProductVariantController::class, 'destroy'])->middleware('permission:variants.delete');
    });
});
