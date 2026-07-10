<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ServiceCommentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Public and Protected API Routes (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

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

    /*
     * Comments on Services (Public read, Auth required to post)
     * - GET  /services/{service}/comments : List active comments (Public; owners/admin see all)
     * - POST /services/{service}/comments : Add a comment (Resident only)
     */
    Route::get('/services/{service}/comments', [ServiceCommentController::class, 'index']);

    // --- Protected Routes ---
    Route::middleware(['auth:sanctum'])->group(function () {

        /*
         * Contact (User → Admin)
         * - POST /contact    : Send a contact message (Any authenticated user)
         * - GET  /contact/my : View own messages (Any authenticated user)
         */
        Route::post('/contact', [ContactController::class, 'store']);
        Route::get('/contact/my', [ContactController::class, 'myMessages']);

        /*
         * Comments (Residents can post)
         * - POST /services/{service}/comments : Add a comment
         */
        Route::post('/services/{service}/comments', [ServiceCommentController::class, 'store']);

        // --- Provider-Only Routes ---
        Route::middleware(['provider'])->group(function () {

            // Type Mutator Operations
            Route::post('/types', [TypeController::class, 'store']);
            Route::put('/types/{type}', [TypeController::class, 'update']);
            Route::delete('/types/{type}', [TypeController::class, 'destroy']);

            // Service Mutator Operations
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{service}', [ServiceController::class, 'update']);
            Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
        });

        // --- Admin-Only Routes ---
        Route::middleware(['admin'])->prefix('admin')->group(function () {

            /*
             * Admin: Contact Messages Management
             * - GET    /admin/contact               : List all messages
             * - GET    /admin/contact/{message}     : View a message (marks as read)
             * - POST   /admin/contact/{message}/reply : Reply to a message
             * - DELETE /admin/contact/{message}     : Delete a message
             */
            Route::get('/contact', [ContactController::class, 'index']);
            Route::get('/contact/{contactMessage}', [ContactController::class, 'show']);
            Route::post('/contact/{contactMessage}/reply', [ContactController::class, 'reply']);
            Route::delete('/contact/{contactMessage}', [ContactController::class, 'destroy']);

            /*
             * Admin: Comments Moderation
             * - GET    /admin/comments                    : List all comments
             * - PATCH  /admin/comments/{comment}/toggle   : Toggle visibility (show/hide)
             * - DELETE /admin/comments/{comment}          : Permanently delete a comment
             */
            Route::get('/comments', [ServiceCommentController::class, 'adminIndex']);
            Route::patch('/comments/{serviceComment}/toggle', [ServiceCommentController::class, 'toggle']);
            Route::delete('/comments/{serviceComment}', [ServiceCommentController::class, 'destroy']);
        });
    });
});
