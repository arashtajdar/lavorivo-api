<?php
namespace App\Http\Controllers;

use App\Http\Requests\Shop\AddUserToShopRequest;
use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Models\History;
use App\Services\ShopService;
use App\Services\HistoryService;
use App\Repositories\ShopRepository;
use App\Models\User;

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
}
