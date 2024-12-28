<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Shop::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);
        $request['owner'] = auth()->id();
        $shop = Shop::create($request->all());

        return response()->json($shop, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Shop::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
        ]);

        $shop->update($request->all());

        return response()->json($shop);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();

        return response()->json(['message' => 'Shop deleted'], 200);
    }

    public function addUserToShop(Request $request, $shopId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $shop = Shop::findOrFail($shopId);
        $user = User::findOrFail($validated['user_id']);

        // Attach the user to the shop if not already attached
        if (!$shop->users()->where('user_id', $user->id)->exists()) {
            $shop->users()->attach($user->id);
            return response()->json(['message' => 'User added to shop successfully.'], 200);
        }

        return response()->json(['message' => 'User is already assigned to this shop.'], 409);
    }

    public function removeUserFromShop($shopId, $userId)
    {
        $shop = Shop::findOrFail($shopId);
        $user = User::findOrFail($userId);

        // Detach the user from the shop
        if ($shop->users()->where('user_id', $user->id)->exists()) {
            $shop->users()->detach($user->id);
            return response()->json(['message' => 'User removed from shop successfully.'], 200);
        }

        return response()->json(['message' => 'User is not assigned to this shop.'], 404);
    }

    public function shopsByEmployer()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $shops = Shop::where('owner', auth()->id())->get();

        return response()->json($shops);
    }

    public function usersByShop($shopId)
    {
        // Ensure the shop exists
        $shop = Shop::findOrFail($shopId);

        // Fetch users associated with the shop
        $users = $shop->users()->get();

        return response()->json($users);
    }

    public function grantAdminAccess(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Ensure the user is already part of the shop
        $existingRelation = $shop->users()->where('users.id', $user->id)->first();

        if (!$existingRelation) {
            return response()->json(['error' => 'User is not a member of this shop'], 400);
        }

        // Update or create the admin role in the pivot table
        $shop->users()->updateExistingPivot($user->id, ['role' => 2]); // 2 for Admin

        return response()->json(['message' => 'Admin access granted successfully'], 200);
    }

    public function revokeAdminAccess(Shop $shop, User $user)
    {
        // Ensure the user is part of the shop
        $existingRelation = $shop->users()->where('users.id', $user->id)->first();

        if (!$existingRelation) {
            return response()->json(['error' => 'User is not associated with this shop'], 404);
        }

        // Check if the user is currently an admin
        if ($existingRelation->pivot->role !== 2) {
            return response()->json(['message' => 'User is not an admin'], 400);
        }

        // Update the role to regular employee (1)
        $shop->users()->updateExistingPivot($user->id, ['role' => 1]); // 1 for regular employee

        return response()->json(['message' => 'Admin access revoked successfully'], 200);
    }

    public function userIsShopAdmin(Shop $shop, User $user)
    {
        $shopUser = DB::table('shop_user')
            ->where('shop_id', $shop->getAttribute('id'))
            ->where('user_id', $user->getAttribute('id'))
            ->where('role', 2)
            ->get();


        return response()->json($shopUser, 200);
    }

}
