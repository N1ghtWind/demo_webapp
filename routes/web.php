<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;

Route::get('/images/{path}', [MediaController::class, 'publicImage'])->where('path', '.*');

// Scramble API Documentation
if (class_exists(\Dedoc\Scramble\Http\Controllers\DocsController::class)) {
    Route::get('docs/api', function () {
        return app(\Dedoc\Scramble\Http\Controllers\DocsController::class)->ui();
    })->name('scramble.docs.index')->middleware(['web', \Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess::class]);
}

Route::get(
    '/{any?}',
    function () {
        return response()->json(['message' => 'Welcome to the API']);
    }
)->where('any', '^(?!api\/|docs\/)[\/\w\.\,-]*');
