<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Permission\PermissionController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
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
});
