<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check credentials
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate token
        $user = Auth::user();
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        // Validate the request data
        $validate = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => User::USER_ROLE_MANAGER,
            'password' => Hash::make($request->password), // Hash the password
        ]);
        $user->sendEmailVerificationNotification();

        // Generate token
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        $message = "Welcome! To begin using the system, create a new shop, create some users and then add shops to the users so you can start managing the shifts.";
        NotificationService::create($user->getId(), Notification::NOTIFICATION_TYPE_SYSTEM, $message, []);

        // Return response
        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['error' => __($status)], 400);
    }

}
