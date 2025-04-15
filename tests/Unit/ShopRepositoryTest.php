<?php

namespace Tests\Unit;

use App\Models\Shop;
use App\Models\User;
use App\Repositories\ShopRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShopRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $shopRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopRepository = new ShopRepository();
    }

    /**
     * Test finding a shop by ID
     *
     * @return void
     */
    public function test_find_by_id()
    {
        // Create a shop
        $shop = Shop::factory()->create();
        
        // Find the shop by ID
        $foundShop = $this->shopRepository->findById($shop->id);
        
        // Assert the shop was found
        $this->assertEquals($shop->id, $foundShop->id);
        $this->assertEquals($shop->name, $foundShop->name);
        $this->assertEquals($shop->location, $foundShop->location);
        $this->assertEquals($shop->owner, $foundShop->owner);
    }

    /**
     * Test getting shop IDs by user and role
     *
     * @return void
     */
    public function test_get_shop_ids_by_user_and_role()
    {
        // Create a user and a shop
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        
        // Create a shop_user entry with a specific role
        DB::table('shop_user')->insert([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'role' => Shop::SHOP_USER_ROLE_MANAGER,
        ]);
        
        // Get shop IDs by user and role
        $shopIds = $this->shopRepository->getShopIdsByUserAndRole($user->id, Shop::SHOP_USER_ROLE_MANAGER);
        
        // Assert the shop ID was found
        $this->assertCount(1, $shopIds);
        $this->assertEquals($shop->id, $shopIds[0]);
    }
} 