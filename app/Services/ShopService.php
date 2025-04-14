<?php
namespace App\Services;

use App\Models\History;
use App\Models\Shop;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\HistoryService;
use Illuminate\Support\Facades\Log;

class ShopService
{
    public function createShop($validated, $user)
    {
        if ($user->employer) {
            Log::error('Cannot create shop because customer has employer', $validated);
            return response()->json(["message" => "You cannot create a new shop!"], 403);
        }

        if (count($user->ownedShops) >= $user->subscription->maximum_shops) {
            Log::error("Maximum shops reached. Upgrade to have more shops!", $validated);
            return response()->json(["message" => "Maximum shops reached. Upgrade to have more shops!"], 400);
        }

        $shop = Shop::create($validated);
        HistoryService::log(History::ADD_SHOP, $validated);

        $message = "New shop created: " . $shop->name;
        NotificationService::create($user->id, Notification::NOTIFICATION_TYPE_NEW_SHOP_CREATED, $message, ["shopId" => $shop->id]);

        return $shop;
    }

    public function addUserToShop($shop, $user)
    {
        if (!$shop->users()->where('user_id', $user->id)->exists()) {
            $shop->users()->attach($user->id);
            return response()->json(['message' => 'User added to shop successfully.'], 200);
        }

        HistoryService::log(History::ADD_USER_TO_SHOP, [
            'shop_id' => $shop->id,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'User is already assigned to this shop.'], 409);
    }

    public function removeUserFromShop($shop, $user)
    {
        if ($shop->users()->where('user_id', $user->id)->exists()) {
            $shop->users()->detach($user->id);
            return response()->json(['message' => 'User removed from shop successfully.'], 200);
        }

        HistoryService::log(History::REMOVE_USER_FROM_SHOP, [
            'shop_id' => $shop->id,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'User is not assigned to this shop.'], 404);
    }
}
