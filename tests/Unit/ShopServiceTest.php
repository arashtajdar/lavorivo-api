<?php

namespace Tests\Unit;

use App\Models\Shop;
use App\Models\User;
use App\Repositories\ShopRepository;
use App\Services\ShopService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class ShopServiceTest extends TestCase
{
    protected $shopService;
    protected $shopRepositoryMock;
    protected $user;
    protected $shop;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->shopRepositoryMock = Mockery::mock(ShopRepository::class);
        
        // Create the service with mocked dependencies
        $this->shopService = new ShopService($this->shopRepositoryMock);
        
        // Create test data
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        $this->shop = Mockery::mock(Shop::class);
        $this->shop->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->shop->shouldReceive('getAttribute')->with('state')->andReturn(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test createShop method
     */
    public function test_create_shop()
    {
        // Prepare test data
        $shopData = [
            'name' => 'Test Shop',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'owner' => 1
        ];
        
        // Mock the repository to create the shop
        $this->shopRepositoryMock->shouldReceive('create')
            ->once()
            ->with($shopData)
            ->andReturn($this->shop);
        
        // Call the method
        $result = $this->shopService->createShop($shopData, $this->user);
        
        // Assert result
        $this->assertEquals($this->shop, $result);
    }

    /**
     * Test addUserToShop method
     */
    public function test_add_user_to_shop()
    {
        // Create a mock user
        $newUser = Mockery::mock(User::class);
        $newUser->shouldReceive('getAttribute')->with('id')->andReturn(2);
        
        // Mock the shop to add the user
        $this->shop->shouldReceive('users->attach')
            ->once()
            ->with(2, ['role' => 'customer'])
            ->andReturn(true);
        
        // Call the method
        $result = $this->shopService->addUserToShop($this->shop, $newUser);
        
        // Assert result
        $this->assertEquals(['message' => 'User added to shop successfully'], $result);
    }

    /**
     * Test removeUserFromShop method
     */
    public function test_remove_user_from_shop()
    {
        // Create a mock user
        $userToRemove = Mockery::mock(User::class);
        $userToRemove->shouldReceive('getAttribute')->with('id')->andReturn(2);
        
        // Mock the shop to remove the user
        $this->shop->shouldReceive('users->detach')
            ->once()
            ->with(2)
            ->andReturn(true);
        
        // Call the method
        $result = $this->shopService->removeUserFromShop($this->shop, $userToRemove);
        
        // Assert result
        $this->assertEquals(['message' => 'User removed from shop successfully'], $result);
    }

    /**
     * Test getShopsByEmployer method
     */
    public function test_get_shops_by_employer()
    {
        // Mock the repository to return shops
        $this->shopRepositoryMock->shouldReceive('findByOwner')
            ->once()
            ->with(1)
            ->andReturn(new Collection([$this->shop]));
        
        // Call the method
        $result = $this->shopService->getShopsByEmployer($this->user);
        
        // Assert result
        $this->assertCount(1, $result);
        $this->assertEquals($this->shop, $result->first());
    }

    /**
     * Test getUsersByShop method
     */
    public function test_get_users_by_shop()
    {
        // Create a mock user
        $shopUser = Mockery::mock(User::class);
        
        // Mock the shop to return users
        $this->shop->shouldReceive('getAttribute')
            ->with('users')
            ->andReturn(new Collection([$shopUser]));
        
        // Call the method
        $result = $this->shopService->getUsersByShop($this->shop);
        
        // Assert result
        $this->assertCount(1, $result);
        $this->assertEquals($shopUser, $result->first());
    }

    /**
     * Test updateUserRoleInShop method
     */
    public function test_update_user_role_in_shop()
    {
        // Create a mock user
        $userToUpdate = Mockery::mock(User::class);
        $userToUpdate->shouldReceive('getAttribute')->with('id')->andReturn(2);
        
        // Mock the shop to update the user role
        $this->shop->shouldReceive('users->updateExistingPivot')
            ->once()
            ->with(2, ['role' => Shop::SHOP_USER_ROLE_MANAGER])
            ->andReturn(true);
        
        // Call the method
        $result = $this->shopService->updateUserRoleInShop($this->shop, $userToUpdate, Shop::SHOP_USER_ROLE_MANAGER);
        
        // Assert result
        $this->assertTrue($result);
    }

    /**
     * Test userIsShopAdmin method
     */
    public function test_user_is_shop_admin()
    {
        // Create a mock user
        $userToCheck = Mockery::mock(User::class);
        $userToCheck->shouldReceive('getAttribute')->with('id')->andReturn(2);
        
        // Mock the shop to check if the user is an admin
        $this->shop->shouldReceive('users->where')
            ->once()
            ->with('id', 2)
            ->andReturn(Mockery::mock(Collection::class)->shouldReceive('first')->andReturn(
                Mockery::mock()->shouldReceive('getAttribute')->with('pivot')->andReturn(
                    Mockery::mock()->shouldReceive('getAttribute')->with('role')->andReturn(Shop::SHOP_USER_ROLE_MANAGER)->getMock()
                )->getMock()
            )->getMock());
        
        // Call the method
        $result = $this->shopService->userIsShopAdmin($this->shop, $userToCheck);
        
        // Assert result
        $this->assertTrue($result);
    }

    /**
     * Test toggleState method
     */
    public function test_toggle_state()
    {
        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->shop);
        
        // Mock the shop to toggle the state
        $this->shop->shouldReceive('update')
            ->once()
            ->with(['state' => true])
            ->andReturn(true);
        
        // Call the method
        $result = $this->shopService->toggleState($this->user, 1, true);
        
        // Assert result
        $this->assertEquals($this->shop, $result);
    }

    /**
     * Test getShopRules method
     */
    public function test_get_shop_rules()
    {
        // Prepare test data
        $rules = [
            'rule1' => 'No smoking',
            'rule2' => 'No pets allowed'
        ];
        
        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->shop);
        
        // Mock the shop to return rules
        $this->shop->shouldReceive('getAttribute')
            ->with('rules')
            ->andReturn($rules);
        
        // Call the method
        $result = $this->shopService->getShopRules(1);
        
        // Assert result
        $this->assertEquals($rules, $result);
    }
} 