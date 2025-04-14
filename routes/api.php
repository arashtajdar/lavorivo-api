<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftLabelController;
use App\Http\Controllers\ShiftSwapController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserOffDayController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/health', [HealthController::class, 'check']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);


// Email verification routes
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->name('verification.verify');

Route::post('/email/resend', [VerifyEmailController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1']);

Route::post('/email/resend-for-employee', [VerifyEmailController::class, 'resendForEmployee'])
    ->middleware(['auth:sanctum', 'throttle:6,1']);

Route::get('/email/verify/check', [VerifyEmailController::class, 'check'])
    ->middleware('auth:sanctum');

// Password reset routes
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Stripe routes
Route::prefix('stripe')->group(function () {
    Route::post('/webhook', [StripeController::class, 'handleWebhook']);

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('/checkout', [StripeController::class, 'createCheckoutSession']);
    });
});

// Protected routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // User profile routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'getProfile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/change-password', [UserController::class, 'changePassword']);
        Route::get('/reports/main', [UserController::class, 'mainReport']);
    });

    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/employer', [UserController::class, 'usersByEmployer']);
        Route::get('/listUsersToManage', [UserController::class, 'listUsersToManage']);
        Route::post('/reject-manager', [UserController::class, 'rejectManager']);
        Route::post('/addEmployee', [UserController::class, 'addEmployee']);
        Route::post('/removeEmployee', [UserController::class, 'removeEmployee']);
        Route::apiResource('/', UserController::class)->except(['create', 'edit'])->names('users');
    });

    // Shop routes
    Route::prefix('shops')->group(function () {
        Route::get('/employer', [ShopController::class, 'shopsByEmployer']);
        Route::patch('/{id}/toggle-state/{state}', [ShopController::class, 'toggleState']);
        Route::post('/{shop}/grantAdmin/{user}', [ShopController::class, 'grantAdminAccess']);
        Route::post('/{shop}/revokeAdmin/{user}', [ShopController::class, 'revokeAdminAccess']);
        Route::get('/{shop}/isUserAdmin/{user}', [ShopController::class, 'userIsShopAdmin']);
        Route::post('/{shopId}/users', [ShopController::class, 'addUserToShop']);
        Route::delete('/{shopId}/users/{userId}', [ShopController::class, 'removeUserFromShop']);
        Route::get('/{shopId}/rules', [ShopController::class, 'getShopRules']);
        Route::get('/{shopId}/users', [ShopController::class, 'usersByShop']);
        Route::apiResource('/', ShopController::class)->except(['create', 'edit'])->names('shops');
    });

    // Shift routes
    Route::prefix('shifts')->group(function () {
        Route::post('/apply-template', [ShiftController::class, 'applyTemplate']);
        Route::get('/employee-shifts', [ShiftController::class, 'employeeShifts']);
        Route::post('/removeByParams', [ShiftController::class, 'removeShift']);
        Route::post('/auto', [ShiftController::class, 'auto']);
        Route::apiResource('/', ShiftController::class)->except(['create', 'edit'])->names('shifts');
    });

    // Shift label routes
    Route::prefix('shift-labels')->group(function () {
        Route::get('/all', [ShiftLabelController::class, 'getAllShiftLabels']);
        Route::get('/active', [ShiftLabelController::class, 'getAllActive']);
        Route::patch('/{id}/update-active-status', [ShiftLabelController::class, 'updateActiveStatus']);
        Route::apiResource('/', ShiftLabelController::class)->except(['create', 'edit'])->names('shift-labels');
    });

    // Shift swap routes
    Route::prefix('shift-swap')->group(function () {
        Route::post('/request', [ShiftSwapController::class, 'requestSwap']);
        Route::get('/requests', [ShiftSwapController::class, 'getRequests']);
        Route::post('/approve/{id}', [ShiftSwapController::class, 'approveRequest']);
        Route::post('/reject/{id}', [ShiftSwapController::class, 'rejectRequest']);
        Route::get('/user-requests', [ShiftSwapController::class, 'getUserRequests']);
    });

    // User off days routes
    Route::prefix('user-off-days')->group(function () {
        Route::get('/listOffDaysToManage', [UserOffDayController::class, 'listOffDaysToManage']);
        Route::post('/UpdateOffDayStatus', [UserOffDayController::class, 'UpdateOffDayStatus']);
        Route::resource('/', UserOffDayController::class)->except(['create', 'edit'])->names('user-off-days');
    });

    // Rule routes
    Route::prefix('rules')->group(function () {
        Route::post('/deleteByParams', [RuleController::class, 'deleteByParams']);
        Route::apiResource('/', RuleController::class)->except(['create', 'edit'])->names('rules');
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/read/{id}', [NotificationController::class, 'markAsRead']);
    });

    // Subscription routes
    Route::prefix('subscription')->group(function () {
        Route::post('/validate', [SubscriptionController::class, 'validatePurchase']);
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    });
});




