<?php

namespace App\Http\Controllers;

use App\Mail\ManagerRemovedUser;
use App\Mail\ManagerVerification;
use App\Mail\NewEmployeeRegistration;
use App\Mail\RequestToRegister;
use App\Models\Notification;
use App\Models\User;
use App\Models\Shop;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // List all users
    public function index()
    {
        return response()->json(User::with('shops')->get());
    }

    // Show a specific user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Create a new user
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);
            $validated['role'] = User::USER_ROLE_Customer;
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(), // Returns detailed validation error messages
            ], 422); // HTTP status code for Unprocessable Entity
        }


        $validated['password'] = bcrypt($validated['password']); // Hash password

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function removeEmployee(Request $request)
    {
        try {
            $currentManagerId = auth()->id();
            $validated = $request->validate([
                'user_id' => 'required'
            ]);
            $userId = $validated['user_id'];
            $user = User::findOrFail($userId);
            $user->delete();
            Mail::to($user->email)->send(new ManagerRemovedUser());
            return response()->json(
                ['message' => 'User removed!'],
                201);
        } catch (Exception $e) {
            //var_dump($e->getMessage());
        }

    }

    public function addEmployee(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email'
        ]);
        $currentManagerId = auth()->id();
        if(count(auth()->user()->employees) >= auth()->user()->subscription->maximum_employees){
            return response()->json(["message" => "Maximum Employees reached. Upgrade to have more employees!"], 400);
        }
        try {

            $user = User::firstWhere('email', $validated['email']);
            if ($user) {
                // email already exist
                Mail::to($validated['email'])->send(new ManagerVerification());
                return response()->json(
                    ['message' => 'Email already Exist!'],
                    201);
            } else {
                $rawPassword = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
                $validated['password'] = bcrypt($rawPassword);
                $validated['employer'] = $currentManagerId;
                $validated['role'] = 1; // Assuming '1' is the role for employees

                // Create the new user
                $user = User::create($validated);

                $verificationUrl = URL::temporarySignedRoute(
                    'verification.verify',
                    Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                    [
                        'id' => $user->id,
                        'hash' => sha1($user->email),
                    ]
                );

                // Send email with password and verification link
                Mail::to($validated['email'])->send(new NewEmployeeRegistration($user, $rawPassword, $verificationUrl));
                $message = "New employee created: ". $user->email;
                NotificationService::create(auth()->id(), Notification::NOTIFICATION_TYPE_NEW_EMPLOYEE_CREATED, $message, ["id" => $user->id]);

                return response()->json(
                    ['message' => 'Account created and email sent with credentials!'],
                    201);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Failed to add the user',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Delete all shops owned by this user (Using `ownedShops` relation)
        $user->ownedShops()->delete();

        // Delete all employees managed by this user (Using `employees` relation)
        $user->employees()->delete();

        // Delete the user itself
        $user->delete();

        return response()->json(['message' => 'User and associated data deleted successfully']);
    }


    // Retrieve users connected to a specific shop
    public function usersByShop($shopId)
    {
        $currentUser = auth()->user();
        $shop = Shop::where('id', $shopId)->firstOrFail();
        $isShopManager = DB::table('shop_user')
            ->where('shop_id', $shopId)
            ->where('user_id', $currentUser->id)
            ->where('role', Shop::SHOP_USER_ROLE_MANAGER)
            ->pluck('user_id')->toArray();
        if ($shop->owner != $currentUser->id) { // if current user is not shop owner
            if (!!count($isShopManager)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $shop = Shop::findOrFail($shopId);
        $users = $shop->users;

        return response()->json($users);
    }

    public function usersByEmployer()
    {
        $users = User::where('employer', auth()->id())->with('shops')->get();

        return response()->json($users);
    }

    public function listUsersToManage()
    {
        $currentManagerId = auth()->id();
        $employees = User::where('employer', $currentManagerId)->with('shops')->get()->toArray();
        $res = [];
        foreach ($employees as $employee) {
            $response = [];

            $response['id'] = $employee['id'];
            $response['is_active'] = (bool)$employee['email_verified_at'];
            $response['name'] = $employee['name'];
            $response['email'] = $employee['email'];
            $response['email_verified_at'] = $employee['email_verified_at'];
            $response['shops'] = $employee['shops'];
            $res[] = $response;

        }
        return response()->json($res, 200);
    }

    public function getProfile()
    {
        return response()->json(auth()->user());
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $user->name = $validated['name'];
        $user->save();

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['error' => 'Current password is incorrect.'], 400);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public static function CheckIfUserCanManageThisShop($userId, $shopId){
        $ownThisShop = Shop::where([
            'id'=> $shopId,
            'owner'=> $userId
        ])->first();
        $manageThisShop = DB::table('shop_user')
            ->where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('role', Shop::SHOP_USER_ROLE_MANAGER)
            ->first();
        if (!$ownThisShop && !$manageThisShop){
            return false;
        }
        return true;
    }


}

