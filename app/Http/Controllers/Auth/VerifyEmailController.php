<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Verify a user's email address.
     *
     * @param int $id The user ID
     * @param string $hash The verification hash
     * @return JsonResponse|RedirectResponse
     */
    public function verify(int $id, string $hash): JsonResponse|RedirectResponse
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!hash_equals($hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already Verified'], 200);
        }

        $user->markEmailAsVerified();
        $redirectUrl = config('app.frontend_email_verified_url', 'https://app.lavorivo.com/auth/email-verified');

        return redirect()->to($redirectUrl);
    }

    /**
     * Resend the email verification notification.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }
        
        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent']);
    }

    /**
     * Resend verification email for an employee.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendForEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id'
        ]);

        $employerId = auth()->id(); // Current logged-in user
        $employee = User::where('id', $validated['employee_id'])
            ->where('employer', $employerId)
            ->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found or unauthorized'], 403);
        }

        if ($employee->hasVerifiedEmail()) {
            return response()->json(['message' => 'Employee email is already verified']);
        }

        $employee->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent to employee']);
    }

    /**
     * Check if the user's email is verified.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail(),
            'email' => $request->user()->email
        ]);
    }
}
