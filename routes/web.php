<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;

Route::get('/images/{path}', [MediaController::class, 'publicImage'])->where('path', '.*');

Route::get(
    '/{any?}',
    function () {
        return response()->json(['message' => 'Welcome to the API']);
    }
)->where('any', '^(?!api\/|docs\/)[\/\w\.\,-]*');
