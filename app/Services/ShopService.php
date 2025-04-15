<?php

namespace App\Services;

use App\Models\History;
use App\Models\Rule;
use App\Models\ShiftLabel;
use App\Models\Shop;
use App\Models\User;
use App\Models\Notification;
use App\Repositories\ShiftLabelRepository;
use App\Repositories\ShopRepository;
use App\Services\NotificationService;
use App\Services\HistoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ShopService
{
    protected $shopRepository;

    public function __construct(ShopRepository $shopRepository)
    {
        $this->shopRepository = $shopRepository;
    }

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

    public function getShopsByEmployer(User $user): Collection
    {
        $managerIds = $this->shopRepository->getShopIdsByUserAndRole($user->id, Shop::SHOP_USER_ROLE_MANAGER);
        $customerIds = $this->shopRepository->getShopIdsByUserAndRole($user->id, Shop::SHOP_USER_ROLE_CUSTOMER);

        $ownedShops = $this->shopRepository->getShopsOwnedBy($user->id);
        $allShops = $this->shopRepository->getShopsByIds(array_merge($managerIds, $customerIds))
            ->merge($ownedShops);

        return $allShops->map(function ($shop) use ($user, $managerIds) {
            return array_merge($shop->toArray(), [
                'manager' => in_array($shop->id, $managerIds),
                'owner' => $shop->owner == $user->id,
            ]);
        });
    }

    public function getUsersByShop(Shop $shop): Collection
    {
        return $shop->users()->get();
    }

    public function updateUserRoleInShop(Shop $shop, User $user, int $role): void
    {
        $shop->users()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public function userIsShopAdmin(Shop $shop, User $user): bool
    {
        return $this->shopRepository->userHasRoleInShop($shop->id, $user->id, Shop::SHOP_USER_ROLE_MANAGER);
    }

    public function toggleState(User $user, int $shopId, bool $state): Shop
    {
        $shop = $this->shopRepository->findOwnedShop($user->id, $shopId);
        if (!$shop) {
            throw new \Exception('Unauthorized');
        }
        $shop->state = $state;
        $shop->save();
        return $shop;
    }

    public function getShopRules(int $shopId): array
    {
        $shop = Shop::findOrFail($shopId);
        $users = $shop->users()->get();
        $shiftLabels = ShiftLabel::where('shop_id', $shopId)->get();

        return $users->map(function ($user) use ($shopId, $shiftLabels) {
            $restrictedLabels = Rule::where('shop_id', $shopId)
                ->where('rule_type', Rule::RULE_TYPE_EXCLUDE_LABELS)
                ->where('employee_id', $user->id)
                ->pluck('rule_data')->toArray();

            $restrictedWeekDays = Rule::where('shop_id', $shopId)
                ->where('rule_type', Rule::RULE_TYPE_EXCLUDE_DAYS)
                ->where('employee_id', $user->id)
                ->pluck('rule_data')->toArray();

            return array_merge($user->toArray(), [
                'shift_labels' => $shiftLabels->map(function ($label) use ($restrictedLabels) {
                    $restrictedDays = array_filter($restrictedLabels, fn($data) => $data['label_id'] === $label->id);
                    return array_merge($label->toArray(), ['restrictedDays' => $restrictedDays]);
                })->toArray(),
                'restricted_week_days' => $restrictedWeekDays,
            ]);
        })->toArray();
    }

}
