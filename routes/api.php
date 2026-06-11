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
    Route::get('/services/{service}/listings', [ServiceController::class, 'getServiceListings']);
    Route::get('/services/{service}/listings/{listing}', [ServiceController::class, 'showListing']);
    Route::apiResource('/print-services', PrintServiceController::class);
});


