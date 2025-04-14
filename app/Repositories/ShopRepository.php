<?php
namespace App\Repositories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ShopRepository
{
    public function findById($id)
    {
        return Shop::findOrFail($id);
    }

    public function create(array $data)
    {
        return Shop::create($data);
    }

    public function deleteById($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();
        return $shop;
    }
    public function getShopIdsByUserAndRole(int $userId, int $role): array
    {
        return DB::table('shop_user')
            ->where('user_id', $userId)
            ->where('role', $role)
            ->pluck('shop_id')
            ->toArray();
    }

    public function getShopsOwnedBy(int $userId): Collection
    {
        return Shop::where('owner', $userId)->get();
    }

    public function getShopsByIds(array $ids): Collection
    {
        return Shop::whereIn('id', $ids)->get();
    }

    public function userHasRoleInShop(int $shopId, int $userId, int $role): bool
    {
        return DB::table('shop_user')
            ->where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('role', $role)
            ->exists();
    }

    public function findOwnedShop(int $ownerId, int $shopId): ?Shop
    {
        return Shop::where('id', $shopId)->where('owner', $ownerId)->first();
    }

}
