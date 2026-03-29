<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth:sanctum')->get('/sso/authorize', [\App\Http\Controllers\Auth\SsoController::class, 'authorize']);
