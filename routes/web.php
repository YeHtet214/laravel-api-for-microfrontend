<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request as Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->post('/login', [LoginController::class, '__invoke']);
Route::middleware('auth')->post('/logout', [AuthController::class, 'logout']);