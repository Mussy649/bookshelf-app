<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // GET APIは未認証でも利用可能
    Route::apiResource('books', BookController::class)
        ->only(['index', 'show']);

    // POST / PUT / DELETEはSanctum認証必須
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('books', BookController::class)
            ->only(['store', 'update', 'destroy']);
    });
});
