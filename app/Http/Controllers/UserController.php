<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function addEmployee(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email'
            ]);
            $validated['password'] = '1234567890';
            $validated['role'] = 1;
            $validated['employer'] = auth()->id();
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
            if(!!count($isShopManager)){
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
        $shopsOwned = Shop::where('owner', $currentAdminId)->pluck('id')->toArray();
        $shopsAdmined = DB::table('shop_user')
            ->where('user_id', $currentAdminId)
            ->where('role', 2)
            ->pluck('shop_id')->toArray();
        $shopIds = array_unique(array_merge($shopsOwned, $shopsAdmined));

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


}

