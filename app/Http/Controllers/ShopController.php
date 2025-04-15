<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\AddUserToShopRequest;
use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Models\History;
use App\Models\Shop;
use App\Services\ShopService;
use App\Services\HistoryService;
use App\Repositories\ShopRepository;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    protected $shopService;
    protected $shopRepository;

    public function __construct(ShopService $shopService, ShopRepository $shopRepository)
    {
        $this->shopService = $shopService;
        $this->shopRepository = $shopRepository;
    }

    public function store(StoreShopRequest $request)
    {
        $validated = $request->validated();
        $validated['owner'] = auth()->id();

        $shop = $this->shopService->createShop($validated, auth()->user());
        return response()->json($shop, 201);
    }

    public function update(UpdateShopRequest $request, $id)
    {
        $shop = $this->shopRepository->findById($id);
        $validated = $request->validated();

        $shop->update($validated);
        HistoryService::log(History::UPDATE_SHOP, $validated);

        return response()->json($shop);
    }

    public function destroy($id)
    {
        $this->shopRepository->deleteById($id);
        HistoryService::log(History::REMOVE_SHOP, ['shop_id' => $id]);

        return response()->json(['message' => 'Shop deleted'], 200);
    }

    public function addUserToShop(AddUserToShopRequest $request, $shopId)
    {
        $validated = $request->validated();
        $shop = $this->shopRepository->findById($shopId);
        $user = User::findOrFail($validated['user_id']);

        return $this->shopService->addUserToShop($shop, $user);
    }

    public function removeUserFromShop($shopId, $userId)
    {
        $shop = $this->shopRepository->findById($shopId);
        $user = User::findOrFail($userId);

        return $this->shopService->removeUserFromShop($shop, $user);
    }

    public function shopsByEmployer(): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $shops = $this->shopService->getShopsByEmployer($user);
        return response()->json($shops);
    }

    public function usersByShop($shopId): JsonResponse
    {
        $shop = Shop::findOrFail($shopId);
        $users = $this->shopService->getUsersByShop($shop);
        return response()->json($users);
    }

    public function grantAdminAccess(Request $request, Shop $shop, User $user): JsonResponse
    {
        $this->shopService->updateUserRoleInShop($shop, $user, Shop::SHOP_USER_ROLE_MANAGER);
        return response()->json(['message' => 'Admin access granted successfully']);
    }

    public function revokeAdminAccess(Request $request, Shop $shop, User $user): JsonResponse
    {
        $this->shopService->updateUserRoleInShop($shop, $user, Shop::SHOP_USER_ROLE_CUSTOMER);
        return response()->json(['message' => 'Admin access revoked successfully']);
    }

    public function userIsShopAdmin(Shop $shop, User $user): JsonResponse
    {
        $isAdmin = $this->shopService->userIsShopAdmin($shop, $user);
        return response()->json(['is_admin' => $isAdmin]);
    }

    public function toggleState($id, $state): JsonResponse
    {
        try {
            $user = auth()->user();
            $shop = $this->shopService->toggleState($user, $id, (bool)$state);
            return response()->json(['message' => 'Shop state updated successfully', 'state' => $shop->state]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function getShopRules($shopId): JsonResponse
    {
        $rules = $this->shopService->getShopRules($shopId);
        return response()->json($rules);
    }

}
