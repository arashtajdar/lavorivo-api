<?php

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::get('/health', function () { echo "it is ok";});
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/shops/employer', [ShopController::class, 'shopsByEmployer']);
Route::get('/shops/{shopId}/users', [ShopController::class, 'usersByShop']);

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
    $user = User::find($id);
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    if (!hash_equals($hash, sha1($user->email))) {
        return response()->json(['message' => 'Invalid verification link'], 400);
    }

    if ($user->hasVerifiedEmail()) {
        return redirect(config('app.frontend_login_url'));
    }

    $user->markEmailAsVerified();
    return redirect(config('app.frontend_login_url'));
})->name('verification.verify');

Route::post('/email/resend', function (Request $request) {
    $user = auth()->user();
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }
    $user->sendEmailVerificationNotification();

    return response()->json(['message' => 'Verification link sent']);
})->middleware(['auth:sanctum', 'throttle:6,1']);


Route::get('/email/verify/check', function (Request $request) {
    return response()->json([
        'verified' => $request->user()->hasVerifiedEmail(),
        'email' => $request->user()->email
    ]);
})->middleware('auth:sanctum');


Route::post('/reset-password', function (Request $request) {
    // Validate the form data
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|confirmed|min:8',
    ]);

    // Attempt to reset the password using the provided token
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => bcrypt($password),
            ])->save();
        }
    );

    // Check if the password reset was successful
    if ($status === Password::PASSWORD_RESET) {
        return response()->json(['message' => 'Password has been reset successfully!']);
    }

    return response()->json(['error' => __($status)], 400);
});

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

//Route::post('/email/resend', function (Request $request) {
//    $request->user()->sendEmailVerificationNotification();
//
//    return response()->json(['message' => 'Verification link sent.']);
//})->middleware(['auth:sanctum'])->name('verification.resend');

Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/stripe/subscription-details', [StripeController::class, 'getSubscriptionDetails']);
    Route::post('/stripe/checkout', [StripeController::class, 'createCheckoutSession']);
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->middleware('auth:sanctum');


    Route::get('/notifications', [NotificationController::class, 'index']); // Get notifications
    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']); // Mark as read

    Route::get('/user-off-days/listOffDaysToManage', [UserOffDayController::class, 'listOffDaysToManage']);
    Route::post('/user-off-days/UpdateOffDayStatus', [UserOffDayController::class, 'UpdateOffDayStatus']);

    Route::post('/shift-swap/request', [ShiftSwapController::class, 'requestSwap']);
    Route::get('/shift-swap/requests', [ShiftSwapController::class, 'getRequests']); // Fetch all requests
    Route::post('/shift-swap/approve/{id}', [ShiftSwapController::class, 'approveRequest']); // Approve a request
    Route::post('/shift-swap/reject/{id}', [ShiftSwapController::class, 'rejectRequest']); // Reject a request
    Route::get('/shift-swap/user-requests', [ShiftSwapController::class, 'getUserRequests']); // Fetch user-specific requests

    Route::get('/user/profile', [UserController::class, 'getProfile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/change-password', [UserController::class, 'changePassword']);


    Route::patch('/shops/{id}/toggle-state/{state}', [ShopController::class, 'toggleState']);
    Route::post('/shops/{shop}/grantAdmin/{user}', [ShopController::class, 'grantAdminAccess']);
    Route::post('/shops/{shop}/revokeAdmin/{user}', [ShopController::class, 'revokeAdminAccess']);
    Route::get('/shops/{shop}/isUserAdmin/{user}', [ShopController::class, 'userIsShopAdmin']);
// Shifts
    Route::apiResource('shifts', ShiftController::class);

// Shops
    Route::apiResource('shops', ShopController::class);

    // other endpoints
    Route::get('/shops/employer', [ShopController::class, 'shopsByEmployer']);


    Route::post('/apply-template', [ShiftController::class, 'applyTemplate']);
    Route::get('/employee-shifts', [ShiftController::class, 'employeeShifts']);

    Route::get('/users/employer', [UserController::class, 'usersByEmployer']);
    Route::get('/users/listUsersToManage', [UserController::class, 'listUsersToManage']);

    Route::post('/reject-manager', [UserController::class, 'rejectManager']);

    Route::get('/users', [UserController::class, 'index']);

    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/users/addEmployee', [UserController::class, 'addEmployee']);
    Route::post('/users/removeEmployee', [UserController::class, 'removeEmployee']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/shops/{shopId}/users', [UserController::class, 'usersByShop']);
    Route::post('/shops/{shopId}/users', [ShopController::class, 'addUserToShop']);
    Route::delete('/shops/{shopId}/users/{userId}', [ShopController::class, 'removeUserFromShop']);

    //shift labels
    Route::get('/all-shift-labels', [ShiftLabelController::class, 'getAllShiftLabels']);
    Route::get('/shift-labels', [ShiftLabelController::class, 'index']);
    Route::get('/active-shift-labels', [ShiftLabelController::class, 'getAllActive']);
    Route::post('/shifts/removeByParams', [ShiftController::class, 'removeShift']);
    Route::post('/shift-labels', [ShiftLabelController::class, 'store']);
    Route::delete('/shift-labels/{id}', [ShiftLabelController::class, 'destroy']);
    Route::put('/shift-labels/{id}', [ShiftLabelController::class, 'update']);



    Route::post('/auto', [ShiftController::class, 'auto']);

    Route::apiResource('rules', RuleController::class);

    Route::post('/rules/deleteByParams', [RuleController::class, 'deleteByParams']);
    Route::get('/shop/{shopId}/rules', [ShopController::class, 'getShopRules']);
    Route::patch('shift-labels/{id}/update-active-status', [ShiftLabelController::class, 'updateActiveStatus']);

    Route::resource('user-off-days', UserOffDayController::class);
});




