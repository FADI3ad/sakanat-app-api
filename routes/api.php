<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PrintServiceController;
use App\Http\Controllers\ServiceController;

use Illuminate\Support\Facades\Route;


Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('v1')->group(function () {
    Route::apiResource('/services', ServiceController::class);
    Route::get('/services/{service}/listings', [ServiceController::class, 'listings']);
    Route::get('/services/{service}/listings/{listing}', [ServiceController::class, 'listingDetails']);
    Route::get('/print-services', [PrintServiceController::class, 'index']);
    Route::get('/print-services/{printService}', [PrintServiceController::class, 'show']);

    Route::middleware(['auth:sanctum', 'provider'])->group(function () {
        Route::post('/print-services', [PrintServiceController::class, 'store']);
    });
});

