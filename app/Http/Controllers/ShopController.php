<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Rule;
use App\Models\ShiftLabel;
use App\Models\Shop;
use App\Models\User;
use App\Services\HistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Translation\t;

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);
        $request['owner'] = auth()->id();
        if(auth()->user()->employer){
            return response()->json(["message" => "You cannot create a new shop!"], 403);
        }
        if(count(auth()->user()->ownedShops) >= auth()->user()->subscription->maximum_shops){
            return response()->json(["message" => "Maximum shops reached. Upgrade to have more shops!"], 400);
        }
        $shop = Shop::create($request->all());
        HistoryService::log(History::ADD_SHOP, $validated);

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

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
        ]);

        $shop->update($request->all());
        HistoryService::log(History::UPDATE_SHOP, $validated);

        return response()->json($shop);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();
        HistoryService::log(History::REMOVE_SHOP, $id);

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
        HistoryService::log(History::ADD_USER_TO_SHOP, [
            'shop_id' => $shopId,
            'user_id' => $user->id,
        ]);

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
        HistoryService::log(History::REMOVE_USER_FROM_SHOP, [
            'shop_id' => $shopId,
            'user_id' => $userId,
        ]);
        return response()->json(['message' => 'User is not assigned to this shop.'], 404);
    }

    public function shopsByEmployer()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userId = $user->id;

        // Retrieve manager and customer shop IDs
        $ShopsManagerIds = DB::table('shop_user')
            ->where('user_id', $userId)
            ->where('role', Shop::SHOP_USER_ROLE_MANAGER)
            ->pluck('shop_id')
            ->toArray();

        $ShopsCustomerIds = DB::table('shop_user')
            ->where('user_id', $userId)
            ->where('role', Shop::SHOP_USER_ROLE_CUSTOMER)
            ->pluck('shop_id')
            ->toArray();

        // Retrieve owned shops
        $shopsOwned = Shop::where('owner', $userId)->get()->toArray();

        // Merge all shops
        $allShops = Shop::whereIn('id', array_merge($ShopsManagerIds, $ShopsCustomerIds))
            ->orWhere('owner', $userId)
            ->get();

        // Add manager and owner flags
        $shops = $allShops->map(function ($shop) use ($userId, $ShopsManagerIds) {
            return array_merge($shop->toArray(), [
                'manager' => in_array($shop->id, $ShopsManagerIds),
                'owner' => $shop->owner == $userId,
            ]);
        });

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

    public function grantAdminAccess(Request $request, Shop $shop, User $user)
    {


        // Ensure the user is already part of the shop
        $existingRelation = $shop->users()->where('users.id', $user->id)->first();

        if (!$existingRelation) {
            return response()->json(['error' => 'User is not a member of this shop'], 400);
        }

        // Update or create the admin role in the pivot table
        $shop->users()->updateExistingPivot($user->id, ['role' => Shop::SHOP_USER_ROLE_MANAGER]); // 2 for Admin

        return response()->json(['message' => 'Admin access granted successfully'], 200);
    }

    public function revokeAdminAccess(Request $request, Shop $shop, User $user)
    {
        // Ensure the user is already part of the shop
        $existingRelation = $shop->users()->where('users.id', $user->id)->first();

        if (!$existingRelation) {
            return response()->json(['error' => 'User is not a member of this shop'], 400);
        }

        // Update or create the admin role in the pivot table
        $shop->users()->updateExistingPivot($user->id, ['role' => Shop::SHOP_USER_ROLE_CUSTOMER]);

        return response()->json(['message' => 'Admin access granted successfully'], 200);
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
    public function toggleState($id, $state)
    {
        $currentUser = auth()->user();

        $shop = Shop::where('id', $id)
            ->where(function ($query) use ($currentUser) {
                $query->where('owner', $currentUser->id);
            })
            ->first();

        if (!$shop) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shop->state = !!$state;
        $shop->save();

        return response()->json(['message' => 'Shop state updated successfully', 'state' => $shop->state]);
    }

    public function getShopRules($shopId)
    {
        $currentUser = auth()->user();

        // Validate if the user owns or manages the shop
        $shop = Shop::find($shopId);

//        if (!$shop) {
//            return response()->json(['error' => 'Unauthorized'], 403);
//        }

        // Fetch all users of the shop
        $users = $shop->users()->get();
        $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();
        $userDataResponse = array();
        $userData = $users->toArray();
        foreach ($userData as $user) {
            $restrictedLabels = Rule::where('shop_id', $shopId)
                ->where('rule_type', Rule::RULE_TYPE_EXCLUDE_LABELS)
                ->where('employee_id', $user['id'])
                ->pluck('rule_data')->toArray();

            $user['shift_labels'] = $shiftLabels->map(function ($label) use ($restrictedLabels) {
                return array_merge(
                    $label->toArray(),
                    ['isRestricted' => in_array($label->id, $restrictedLabels)]
                );
            })->toArray();
            $user['restricted_week_days'] = Rule::where('shop_id', $shopId)
                ->where('rule_type', Rule::RULE_TYPE_EXCLUDE_DAYS)
                ->where('employee_id', $user['id'])
                ->pluck('rule_data')->toArray();
            $userDataResponse[] = $user;
        }

        return response()->json($userDataResponse);
    }



}
