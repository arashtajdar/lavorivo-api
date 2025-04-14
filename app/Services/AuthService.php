<?php

namespace App\Services;

use App\Models\History;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\JsonResponse;

class AuthService
{
    public function login(array $data): JsonResponse
    {
        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            Log::error('Invalid credentials', $data);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('Personal Access Token')->plainTextToken;
        HistoryService::log(History::USER_LOGIN, []);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(array $data): JsonResponse
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => User::USER_ROLE_MANAGER,
            'subscription_id' => Subscription::DEFAULT_SUBSCRIPTION_ID,
            'password' => Hash::make($data['password']),
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('Personal Access Token')->plainTextToken;

        NotificationService::create(
            $user->id,
            Notification::NOTIFICATION_TYPE_SYSTEM,
            "Welcome! To begin using the system, create a new shop...",
            []
        );

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function forgotPassword(array $data): JsonResponse
    {
        $status = Password::sendResetLink(['email' => $data['email']]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        HistoryService::log(History::USER_FORGET_PASSWORD_REQUEST, []);
        return response()->json(['error' => __($status)], 400);
    }
}
