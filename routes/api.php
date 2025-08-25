<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserRegistrationController;

Route::post('/registration', [UserRegistrationController::class, 'registration']);
Route::post('/activation', [UserRegistrationController::class, 'activation'])->name('activation');

Route::post('/admin/auth/login', [AdminAuthController::class, 'login']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth.api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware(['verify.refresh.token'])->get('/auth/refresh-token', [AuthController::class, 'refreshToken']);

Route::get('/product', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'show']);

// Admin routes
require_once __DIR__.'/admin.php';
