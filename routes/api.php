<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])
    ->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user'])
        ->name('auth.user');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAllDevices'])
        ->name('auth.logoutAllDevices');

    Route::get('/users/{id}', [UserController::class, 'show'])
        ->name('users.show');

    Route::apiResource('posts', PostController::class);
});
