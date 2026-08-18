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
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'active_device']);
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
     * - GET /delivery-services                   : List all delivery services (Public)
     * - GET /delivery-services/{deliveryService} : Show a delivery service (Public)
     * - POST /delivery-services                  : Create a delivery service (Protected, Provider Only)
     * - PUT /delivery-services/{deliveryService} : Update a delivery service (Protected, Provider Only)
     * - DELETE /delivery-services/{deliveryService} : Delete a delivery service (Protected, Provider Only)
     */
    Route::get('/delivery-services', [DeliveryServiceController::class, 'index']);
    Route::get('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'show']);

    /*
     * Comments on Services (Public read, Auth required to post)
     * - GET  /services/{service}/comments : List active comments (Public; owners/admin see all)
     * - POST /services/{service}/comments : Add a comment (Resident only)
     */
    Route::get('/services/{service}/comments', [ServiceCommentController::class, 'index']);

    // --- Protected Routes ---
    Route::middleware(['auth:sanctum', 'active_device'])->group(function () {

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

        /*
         * Messages Module
         * - GET  /messages/chats        : Retrieve the list of active chat conversations
         * - GET  /messages/user/{partner}: Retrieve message history with a partner user
         * - POST /messages              : Send a new message
         */
        Route::get('/messages/chats', [MessageController::class, 'myConversations']);
        Route::get('/messages/user/{partner}', [MessageController::class, 'chatHistory']);
        Route::post('/messages', [MessageController::class, 'store']);

        // --- Provider-Only Routes ---
        Route::middleware(['provider'])->group(function () {

            // Area Mutator Operations
            Route::post('/areas', [AreaController::class, 'store']);
            Route::put('/areas/{area}', [AreaController::class, 'update']);
            Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

            // Type Mutator Operations
            Route::post('/types', [TypeController::class, 'store']);
            Route::put('/types/{type}', [TypeController::class, 'update']);
            Route::delete('/types/{type}', [TypeController::class, 'destroy']);

            // Service Mutator Operations
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{service}', [ServiceController::class, 'update']);
            Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

            // Delivery Service Mutator Operations
            Route::post('/delivery-services', [DeliveryServiceController::class, 'store']);
            Route::put('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'update']);
            Route::delete('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'destroy']);
        });

        // --- Resident-Only Routes ---
        Route::middleware(['resident'])->group(function () {
            /*
             * Residence & Attendance Module (Resident)
             * - GET  /resident/my-residence : عرض بيانات السرير والغرفة والسكن الخاص بالطالب
             * - POST /attendance/checkin     : مسح QR وتسجيل الحضور
             * - GET  /attendance/my          : عرض سجل الحضور الشخصي
             */
            Route::get('/resident/my-residence', [BedController::class, 'myResidence']);
            Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
            Route::get('/attendance/my', [AttendanceController::class, 'myLogs']);

            /*
             * Absence / Travel Module (Resident)
             * - POST /resident/absences : تسجيل بلاغ غياب أو سفر
             * - GET  /resident/absences : عرض بلاغات الغياب الخاصة بالطالب
             *   Optional query params:
             *     - active=1    : عرض البلاغات النشطة حالياً فقط
             *     - per_page=N  : عدد النتائج في الصفحة
             */
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

            /*
             * Absence / Travel Module (Property Owner)
             * - GET /properties/{property}/absences : عرض كل بلاغات الغياب في السكن التابع للمالك
             *   Optional query params:
             *     - active=1      : البلاغات النشطة حالياً فقط
             *     - per_page=N    : عدد النتائج في الصفحة
             */
            Route::get('/properties/{property}/absences', [AbsenceController::class, 'ownerAbsences']);

            /*
             * Rooms Module (nested under properties)
             * - GET    /properties/{property}/rooms              : List all rooms
             * - POST   /properties/{property}/rooms              : Add a new room
             * - GET    /properties/{property}/rooms/{room}       : Show room with beds
             * - PUT    /properties/{property}/rooms/{room}       : Update room details
             * - DELETE /properties/{property}/rooms/{room}       : Delete room + its beds
             */
            Route::get('/properties/{property}/rooms', [RoomController::class, 'index']);
            Route::post('/properties/{property}/rooms', [RoomController::class, 'store']);
            Route::get('/properties/{property}/rooms/{room}', [RoomController::class, 'show']);
            Route::put('/properties/{property}/rooms/{room}', [RoomController::class, 'update']);
            Route::delete('/properties/{property}/rooms/{room}', [RoomController::class, 'destroy']);

            /*
             * Beds Module (nested under rooms)
             * - GET    /rooms/{room}/beds           : List all beds in a room
             * - POST   /rooms/{room}/beds           : Add a new bed
             * - GET    /rooms/{room}/beds/{bed}     : Show a specific bed
             * - PUT    /rooms/{room}/beds/{bed}     : Update bed occupant name
             * - DELETE /rooms/{room}/beds/{bed}     : Delete a bed
             */
            Route::get('/rooms/{room}/beds', [BedController::class, 'index']);
            Route::post('/rooms/{room}/beds', [BedController::class, 'store']);
            Route::get('/rooms/{room}/beds/{bed}', [BedController::class, 'show']);
            Route::put('/rooms/{room}/beds/{bed}', [BedController::class, 'update']);
            Route::delete('/rooms/{room}/beds/{bed}', [BedController::class, 'destroy']);

            /*
             * Utility Bills Module (nested under properties)
             * - GET    /properties/{property}/bills                    : List all bills (filter by month/type/is_paid)
             * - POST   /properties/{property}/bills                    : Add a new bill
             * - GET    /properties/{property}/bills/{bill}             : Show a specific bill
             * - PUT    /properties/{property}/bills/{bill}             : Update bill details
             * - PATCH  /properties/{property}/bills/{bill}/pay         : Mark bill as paid
             * - DELETE /properties/{property}/bills/{bill}             : Delete a bill
             */
            Route::get('/properties/{property}/bills', [UtilityBillController::class, 'index']);
            Route::post('/properties/{property}/bills', [UtilityBillController::class, 'store']);
            Route::get('/properties/{property}/bills/{bill}', [UtilityBillController::class, 'show']);
            Route::put('/properties/{property}/bills/{bill}', [UtilityBillController::class, 'update']);
            Route::patch('/properties/{property}/bills/{bill}/pay', [UtilityBillController::class, 'markAsPaid']);
            Route::delete('/properties/{property}/bills/{bill}', [UtilityBillController::class, 'destroy']);

            /*
             * Attendance Module (Property Owner / Admin)
             * - GET   /properties/{property}/attendance          : سجل الحضور العام
             * - GET   /properties/{property}/attendance/daily    : سجل الحضور اليومي
             * - GET   /properties/{property}/attendance/monthly  : سجل الحضور الشهري
             * - GET   /properties/{property}/attendance/summary  : ملخص إحصائي
             * - PATCH /properties/{property}/curfew              : تحديد وقت الكيرفيو
             */
            Route::get('/properties/{property}/attendance', [AttendanceController::class, 'propertyLogs']);
            Route::get('/properties/{property}/attendance/daily', [AttendanceController::class, 'dailyLogs']);
            Route::get('/properties/{property}/attendance/monthly', [AttendanceController::class, 'monthlyLogs']);
            Route::get('/properties/{property}/attendance/summary', [AttendanceController::class, 'summary']);
            Route::patch('/properties/{property}/curfew', [AttendanceController::class, 'updateCurfew']);
        });

        // --- Admin-Only Routes ---
        Route::middleware(['admin'])->prefix('admin')->group(function () {

            /*
             * Admin: Users Management
             * - GET    /admin/users               : List all users (filter by type, is_blocked, search)
             * - POST   /admin/users               : Create a new user
             * - GET    /admin/users/{user}        : Show specific user details
             * - PUT    /admin/users/{user}        : Update user details
             * - PATCH  /admin/users/{user}/block  : Toggle block status (block/unblock)
             * - DELETE /admin/users/{user}        : Delete a user
             */
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::post('/users/{user}/revoke-device', [UserController::class, 'revokeDevice']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::patch('/users/{user}/block', [UserController::class, 'toggleBlock']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);

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

            /*
             * Admin: Properties Management
             * - GET    /admin/properties                    : List all properties
             * - GET    /admin/properties/{property}         : Show specific property
             * - DELETE /admin/properties/{property}         : Delete a property
             */
            Route::get('/properties', [PropertyController::class, 'adminIndex']);
            Route::get('/properties/{property}', [PropertyController::class, 'adminShow']);
            Route::delete('/properties/{property}', [PropertyController::class, 'adminDestroy']);

            /*
             * Admin: Services Moderation
             * - GET    /admin/services                    : List all services
             * - GET    /admin/services/{service}         : Show service details
             * - PATCH  /admin/services/{service}/toggle   : Toggle service availability
             * - DELETE /admin/services/{service}          : Delete any service
             */
            Route::get('/services', [ServiceController::class, 'adminIndex']);
            Route::get('/services/{service}', [ServiceController::class, 'adminShow']);
            Route::patch('/services/{service}/toggle', [ServiceController::class, 'adminToggle']);
            Route::delete('/services/{service}', [ServiceController::class, 'adminDestroy']);

            /*
             * Admin: Service Types Management
             * - POST   /admin/types          : Create service type
             * - PUT    /admin/types/{type}   : Update service type
             * - DELETE /admin/types/{type}   : Delete service type
             */
            Route::post('/types', [TypeController::class, 'store']);
            Route::put('/types/{type}', [TypeController::class, 'update']);
            Route::delete('/types/{type}', [TypeController::class, 'destroy']);

            /*
             * Admin: Delivery Services Management
             * - GET    /admin/delivery-services                    : List delivery services
             * - POST   /admin/delivery-services                    : Create delivery service
             * - PUT    /admin/delivery-services/{deliveryService}  : Update delivery service
             * - DELETE /admin/delivery-services/{deliveryService}  : Delete delivery service
             */
            Route::get('/delivery-services', [DeliveryServiceController::class, 'index']);
            Route::post('/delivery-services', [DeliveryServiceController::class, 'store']);
            Route::put('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'update']);
            Route::delete('/delivery-services/{deliveryService}', [DeliveryServiceController::class, 'destroy']);

            /*
             * Admin: Messages Management (Moderation)
             * - GET    /admin/messages                    : List all messages
             * - DELETE /admin/messages/{message}          : Delete any message
             */
            Route::get('/messages', [MessageController::class, 'adminIndex']);
            Route::delete('/messages/{message}', [MessageController::class, 'adminDestroy']);

            /*
             * Admin: Areas Management
             * - POST   /admin/areas          : Add area
             * - PUT    /admin/areas/{area}   : Update area
             * - DELETE /admin/areas/{area}   : Delete area
             */
            Route::post('/areas', [AreaController::class, 'store']);
            Route::put('/areas/{area}', [AreaController::class, 'update']);
            Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

            /*
             * Admin: System-wide Attendance Overview
             * - GET    /admin/attendance     : List all attendance logs across all properties
             */
            Route::get('/attendance', [AttendanceController::class, 'adminLogs']);

            /*
             * Admin: System-wide Absences Overview
             * - GET    /admin/absences       : List all absence reports across all properties
             */
            Route::get('/absences', [AbsenceController::class, 'adminAbsences']);
        });
    });
});
