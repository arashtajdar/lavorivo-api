<?php

namespace App\Http\Controllers;

use App\Mail\ManagerRemovedUser;
use App\Mail\ManagerVerification;
use App\Models\User;
use App\Models\Shop;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function removeEmployee(Request $request){
        try {
            $currentManagerId = auth()->id();
            $validated = $request->validate([
                'user_id' => 'required'
            ]);
            $userId = $validated['user_id'];
            DB::table('user_manager')->where(
                ['manager_id'=> $currentManagerId,'user_id'=>$userId]
            )->delete();
            Mail::to("arash.tajdar@gmail.com")->send(new ManagerRemovedUser());
            return response()->json(
                ['message' => 'User removed!'],
                201);
        }catch (Exception $e) {
            //var_dump($e->getMessage());
        }

    }
    public function addEmployee(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email'
            ]);
            $user = User::firstWhere('email', $validated['email']);
            if ($user) {
                $currentManagerId = auth()->id();
                $userManager = DB::table('user_manager')
                    ->where(['manager_id' => $currentManagerId,'user_id' => $user->id]);
                if(!$userManager->first()){
                    DB::table('user_manager')->insert([
                        'manager_id' => $currentManagerId,
                        'user_id' => $user->id,
                        'is_active' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                Mail::to("arash.tajdar@gmail.com")->send(new ManagerVerification());
                return response()->json(
                    ['message' => 'Request sent! you should wait for customer to verify the request.'],
                    201);
            } else {
                dd('create user');

                $validated['password'] = '1234567890';
                $validated['role'] = 1; // Assuming '1' is the role for employees
                $validated['created_by'] = auth()->id(); // Set 'created_by' to the current authenticated user
                // Hash the password
                $validated['password'] = bcrypt($validated['password']);

                // Create the new user
                $user = User::create($validated);

                // Add the relationship to the `user_manager` table
                $currentManagerId = auth()->id();
                DB::table('user_manager')->insert([
                    'manager_id' => $currentManagerId,
                    'user_id' => $user->id,
                    'is_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }


        return response()->json($user, 201);
    }

    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
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
        $currentAdminId = auth()->id();

        // Get all shop IDs where the current admin is either the owner or an admin
        $ownedShops = Shop::where('owner', $currentAdminId)->pluck('id')->toArray();
        $ManagedShops = DB::table('shop_user')
            ->where('user_id', $currentAdminId)
            ->where('role', Shop::SHOP_USER_ROLE_MANAGER)
            ->pluck('shop_id')->toArray();
        $shopIds = array_unique(array_merge($ownedShops, $ManagedShops));

        // Get all users linked to these shops, along with their roles
        $users = User::with(['shops' => function ($query) use ($shopIds) {
            $query->whereIn('shops.id', $shopIds)->select('shops.id', 'shops.name', 'shop_user.role');
        }])
            ->whereHas('shops', function ($query) use ($shopIds) {
                $query->whereIn('shop_id', $shopIds);
            })
            ->get();

        return response()->json($users);
    }

    public function getManagedUsers()
    {
        $currentManagerId = auth()->id();
        $employees = DB::table('user_manager')->where('manager_id' , $currentManagerId)->get()->toArray();

        $res = [];
        foreach ($employees as $employee) {
            $response = [];
            $userId = $employee->user_id;

            $response['id'] = $userId;
            $response['is_active'] = $employee->is_active;
            $user = User::where('id',$userId)->with('shops')->first();
            $response['name'] = $user->name;
            $response['email'] = $user->email;
            $response['email_verified_at'] = $user->email_verified_at;
            $response['shops'] = $user->shops;
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

}

