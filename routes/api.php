<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DeliveryServiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServiceCommentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UtilityBillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'admin'])
        ->post('/register', [AuthController::class, 'register']);

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'active_device']);
});

/*
|--------------------------------------------------------------------------
| Public and Protected API Routes (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    /*
     * Areas Endpoints (CRUD)
     * - GET /areas          : List all areas (Public)
     * - GET /areas/{area}   : Show a specific area (Public)
     * - GET /areas/{area}/services : List services in an area (Public)
     */
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::get('/areas/{area}/services', [AreaController::class, 'services']);

    /*
     * Types Endpoints (CRUD)
     * - GET /types        : List all service types (Public)
     * - GET /types/{type} : Show a specific service type (Public)
     * - POST /types       : Create a type (Protected, Provider Only)
     * - PUT /types/{type} : Update a type  (Protected, Provider Only)
     * - DELETE /types/{type} : Delete a type (Protected, Provider Only)
     */
    Route::get('/types', [TypeController::class, 'index']);
    Route::get('/types/{type}', [TypeController::class, 'show']);
    Route::get('/types/{type}/services', [TypeController::class, 'services']);
    Route::get('/types/{type}/delivery-services', [DeliveryServiceController::class, 'byType']);

    /*
     * Services Endpoints (CRUD)
     * - GET /services           : List all services (Public)
     * - GET /services/{service} : Show a service (Public)
     * - POST /services          : Create a service (Protected, Provider Only)
     * - PUT /services/{service} : Update a service (Protected, Owner Only)
     * - DELETE /services/{service} : Delete a service (Protected, Owner Only)
     */
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);
    Route::get('/services/{service}/owner', [ServiceController::class, 'ownerDetails']);
    Route::get('/users/{user}/services', [ServiceController::class, 'byUser']);

    /*
     * Delivery Services Endpoints (CRUD)
     */
    Route::get('/delivery-services', [DeliveryServiceController::class, 'index']);
    Route::get('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'show']);

    /*
     * Comments on Services
     */
    Route::get('/services/{service}/comments', [ServiceCommentController::class, 'index']);

    // --- Protected Routes ---
    Route::middleware(['auth:sanctum', 'active_device'])->group(function () {

        /*
         * Contact (User → Admin)
         */
        Route::post('/contact', [ContactController::class, 'store']);
        Route::get('/contact/my', [ContactController::class, 'myMessages']);

        /*
         * Comments
         */
        Route::post('/services/{service}/comments', [ServiceCommentController::class, 'store']);

        /*
         * Messages Module
         */
        Route::get('/messages/chats', [MessageController::class, 'myConversations']);
        Route::get('/messages/user/{partner}', [MessageController::class, 'chatHistory']);
        Route::post('/messages', [MessageController::class, 'store']);

        // --- Provider-Only Routes ---
        Route::middleware(['provider'])->group(function () {

            Route::post('/areas', [AreaController::class, 'store']);
            Route::put('/areas/{area}', [AreaController::class, 'update']);
            Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

            Route::post('/types', [TypeController::class, 'store']);
            Route::put('/types/{type}', [TypeController::class, 'update']);
            Route::delete('/types/{type}', [TypeController::class, 'destroy']);

            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{service}', [ServiceController::class, 'update']);
            Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

            Route::post('/delivery-services', [DeliveryServiceController::class, 'store']);
            Route::put('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'update']);
            Route::delete('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'destroy']);
        });

        // --- Resident-Only Routes ---
        Route::middleware(['resident'])->group(function () {

            Route::get('/resident/my-residence', [BedController::class, 'myResidence']);
            Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
            Route::get('/attendance/my', [AttendanceController::class, 'myLogs']);

            Route::post('/resident/absences', [AbsenceController::class, 'store']);
            Route::get('/resident/absences', [AbsenceController::class, 'myAbsences']);
        });

        // --- Property Owner Routes ---
        Route::middleware(['property_owner'])->group(function () {

            Route::get('/properties/my', [PropertyController::class, 'myProperties']);
            Route::post('/properties', [PropertyController::class, 'store']);
            Route::get('/properties/{property}', [PropertyController::class, 'show']);
            Route::get('/properties/{property}/qr-data', [PropertyController::class, 'qrData']);
            Route::get('/properties/{property}/residents', [PropertyController::class, 'residents']);
            Route::put('/properties/{property}', [PropertyController::class, 'update']);
            Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);

            Route::get('/properties/{property}/absences', [AbsenceController::class, 'ownerAbsences']);

            Route::get('/properties/{property}/rooms', [RoomController::class, 'index']);
            Route::post('/properties/{property}/rooms', [RoomController::class, 'store']);
            Route::get('/properties/{property}/rooms/{room}', [RoomController::class, 'show']);
            Route::put('/properties/{property}/rooms/{room}', [RoomController::class, 'update']);
            Route::delete('/properties/{property}/rooms/{room}', [RoomController::class, 'destroy']);

            Route::get('/rooms/{room}/beds', [BedController::class, 'index']);
            Route::post('/rooms/{room}/beds', [BedController::class, 'store']);
            Route::get('/rooms/{room}/beds/{bed}', [BedController::class, 'show']);
            Route::put('/rooms/{room}/beds/{bed}', [BedController::class, 'update']);
            Route::delete('/rooms/{room}/beds/{bed}', [BedController::class, 'destroy']);

            Route::get('/properties/{property}/bills', [UtilityBillController::class, 'index']);
            Route::post('/properties/{property}/bills', [UtilityBillController::class, 'store']);
            Route::get('/properties/{property}/bills/{bill}', [UtilityBillController::class, 'show']);
            Route::put('/properties/{property}/bills/{bill}', [UtilityBillController::class, 'update']);
            Route::patch('/properties/{property}/bills/{bill}/pay', [UtilityBillController::class, 'markAsPaid']);
            Route::delete('/properties/{property}/bills/{bill}', [UtilityBillController::class, 'destroy']);

            Route::get('/properties/{property}/attendance', [AttendanceController::class, 'propertyLogs']);
            Route::get('/properties/{property}/attendance/daily', [AttendanceController::class, 'dailyLogs']);
            Route::get('/properties/{property}/attendance/monthly', [AttendanceController::class, 'monthlyLogs']);
            Route::get('/properties/{property}/attendance/summary', [AttendanceController::class, 'summary']);
            Route::patch('/properties/{property}/curfew', [AttendanceController::class, 'updateCurfew']);
        });

        // --- Admin-Only Routes ---
        Route::middleware(['admin'])->prefix('admin')->group(function () {

            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::post('/users/provider-with-service', [UserController::class, 'storeProviderWithService']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::post('/users/{user}/revoke-device', [UserController::class, 'revokeDevice']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::patch('/users/{user}/block', [UserController::class, 'toggleBlock']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);

            Route::get('/contact', [ContactController::class, 'index']);
            Route::get('/contact/{contactMessage}', [ContactController::class, 'show']);
            Route::post('/contact/{contactMessage}/reply', [ContactController::class, 'reply']);
            Route::delete('/contact/{contactMessage}', [ContactController::class, 'destroy']);

            Route::get('/comments', [ServiceCommentController::class, 'adminIndex']);
            Route::patch('/comments/{serviceComment}/toggle', [ServiceCommentController::class, 'toggle']);
            Route::delete('/comments/{serviceComment}', [ServiceCommentController::class, 'destroy']);

            Route::get('/properties', [PropertyController::class, 'adminIndex']);
            Route::get('/properties/{property}', [PropertyController::class, 'adminShow']);
            Route::delete('/properties/{property}', [PropertyController::class, 'adminDestroy']);

            Route::get('/services', [ServiceController::class, 'adminIndex']);
            Route::get('/services/{service}', [ServiceController::class, 'adminShow']);
            Route::patch('/services/{service}/toggle', [ServiceController::class, 'adminToggle']);
            Route::delete('/services/{service}', [ServiceController::class, 'adminDestroy']);

            Route::post('/types', [TypeController::class, 'store']);
            Route::put('/types/{type}', [TypeController::class, 'update']);
            Route::delete('/types/{type}', [TypeController::class, 'destroy']);

            Route::get('/delivery-services', [DeliveryServiceController::class, 'index']);
            Route::post('/delivery-services', [DeliveryServiceController::class, 'store']);
            Route::put('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'update']);
            Route::delete('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'destroy']);

            Route::get('/messages', [MessageController::class, 'adminIndex']);
            Route::delete('/messages/{message}', [MessageController::class, 'adminDestroy']);

            Route::post('/areas', [AreaController::class, 'store']);
            Route::put('/areas/{area}', [AreaController::class, 'update']);
            Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

            Route::get('/attendance', [AttendanceController::class, 'adminLogs']);
            Route::get('/absences', [AbsenceController::class, 'adminAbsences']);
        });
    });
});
