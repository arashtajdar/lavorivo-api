<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    /**
     * Reset the user's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        // Validate the form data
        $validated = $request->validate([
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
            Log::info('Password reset successful', ['email' => $validated['email']]);
            return response()->json(['message' => 'Password has been reset successfully!']);
        }
        
        Log::error('Password reset failed', [
            'error' => __($status),
            'email' => $validated['email']
        ]);

        return response()->json(['error' => __($status)], 400);
    }
} 