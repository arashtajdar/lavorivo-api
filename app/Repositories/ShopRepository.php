<?php
namespace App\Repositories;

use App\Models\Shop;

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
}
