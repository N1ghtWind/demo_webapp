<?php

use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('check.admin.jwt')->group(function () {
    Route::get('/user/get-authenticated-user', [UserController::class, 'getAuthenticatedUser']);

    Route::get('/admin/product', [AdminProductController::class, 'index']);
    Route::get('/admin/product/{id}', [AdminProductController::class, 'show']);
    Route::post('/admin/product', [AdminProductController::class, 'store']);
    Route::put('/admin/product/{id}', [AdminProductController::class, 'update']);
    Route::delete('/admin/product/{id}', [AdminProductController::class, 'destroy']);
    Route::delete('/admin/product/bulk-destroy', [AdminProductController::class, 'bulkDestroy']);
    Route::post('/admin/product/upload-images/{itemId}', [AdminProductController::class, 'uploadImages']);
    Route::post('/admin/product/set-image-to-first', [AdminProductController::class, 'setImageToFirst']);
    Route::post('/admin/product/delete-image', [AdminProductController::class, 'deleteImage']);


});
