<?php

namespace Tests\Unit;

use App\Http\Controllers\ShopController;
use App\Models\History;
use App\Models\Shop;
use App\Models\User;
use App\Services\ShopService;
use App\Repositories\ShopRepository;
use App\Http\Requests\Shop\AddUserToShopRequest;
use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\TestCase;

class ShopControllerTest extends TestCase
{
    protected $shopController;
    protected $shopServiceMock;
    protected $shopRepositoryMock;
    protected $user;
    protected $shop;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->shopServiceMock = Mockery::mock(ShopService::class);
        $this->shopRepositoryMock = Mockery::mock(ShopRepository::class);

        // Create the controller with mocked dependencies
        $this->shopController = new ShopController($this->shopServiceMock, $this->shopRepositoryMock);

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
     * Test store method
     */
    public function test_store()
    {
        // Create a mock request
        $request = Mockery::mock(StoreShopRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'name' => 'Test Shop',
            'description' => 'Test Description',
            'address' => 'Test Address'
        ]);

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn($this->user);

        // Mock the service response
        $this->shopServiceMock->shouldReceive('createShop')
            ->once()
            ->with(Mockery::any(), $this->user)
            ->andReturn($this->shop);

        // Call the method
        $response = $this->shopController->store($request);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test update method
     */
    public function test_update()
    {
        // Create a mock request
        $request = Mockery::mock(UpdateShopRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'name' => 'Updated Shop',
            'description' => 'Updated Description'
        ]);

        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->shop);

        // Mock the shop update
        $this->shop->shouldReceive('update')
            ->once()
            ->with(Mockery::any())
            ->andReturn(true);

        // Call the method
        $response = $this->shopController->update($request, 1);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test destroy method
     */
    public function test_destroy()
    {
        // Mock the repository to delete the shop
        $this->shopRepositoryMock->shouldReceive('deleteById')
            ->once()
            ->with(1)
            ->andReturn(true);

        // Call the method
        $response = $this->shopController->destroy(1);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['message' => 'Shop deleted'], json_decode($response->getContent(), true));
    }

    /**
     * Test addUserToShop method
     */
    public function test_add_user_to_shop()
    {
        // Create a mock request
        $request = Mockery::mock(AddUserToShopRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_id' => 2
        ]);

        // Create a mock user
        $newUser = Mockery::mock(User::class);
        $newUser->shouldReceive('getAttribute')->with('id')->andReturn(2);

        // Mock User::findOrFail
        User::shouldReceive('findOrFail')
            ->once()
            ->with(2)
            ->andReturn($newUser);

        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->shop);

        // Mock the service to add the user
        $this->shopServiceMock->shouldReceive('addUserToShop')
            ->once()
            ->with($this->shop, $newUser)
            ->andReturn(['message' => 'User added to shop successfully']);

        // Call the method
        $response = $this->shopController->addUserToShop($request, 1);

        // Assert response
        $this->assertEquals(['message' => 'User added to shop successfully'], $response);
    }

    /**
     * Test removeUserFromShop method
     */
    public function test_remove_user_from_shop()
    {
        // Create a mock user
        $userToRemove = Mockery::mock(User::class);
        $userToRemove->shouldReceive('getAttribute')->with('id')->andReturn(2);

        // Mock User::findOrFail
        User::shouldReceive('findOrFail')
            ->once()
            ->with(2)
            ->andReturn($userToRemove);

        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->shop);

        // Mock the service to remove the user
        $this->shopServiceMock->shouldReceive('removeUserFromShop')
            ->once()
            ->with($this->shop, $userToRemove)
            ->andReturn(['message' => 'User removed from shop successfully']);

        // Call the method
        $response = $this->shopController->removeUserFromShop(1, 2);

        // Assert response
        $this->assertEquals(['message' => 'User removed from shop successfully'], $response);
    }

    /**
     * Test shopsByEmployer method
     */
    public function test_shops_by_employer()
    {
        // Mock Auth facade
        Auth::shouldReceive('user')->andReturn($this->user);

        // Mock the service to return shops
        $this->shopServiceMock->shouldReceive('getShopsByEmployer')
            ->once()
            ->with($this->user)
            ->andReturn([$this->shop]);

        // Call the method
        $response = $this->shopController->shopsByEmployer();

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test usersByShop method
     */
    public function test_users_by_shop()
    {
        // Create a mock user
        $shopUser = Mockery::mock(User::class);

        // Mock Shop::findOrFail
        Shop::shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($this->shop);

        // Mock the service to return users
        $this->shopServiceMock->shouldReceive('getUsersByShop')
            ->once()
            ->with($this->shop)
            ->andReturn([$shopUser]);

        // Call the method
        $response = $this->shopController->usersByShop(1);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test grantAdminAccess method
     */
    public function test_grant_admin_access()
    {
        // Create a mock request
        $request = Mockery::mock(Request::class);

        // Create a mock user
        $userToGrant = Mockery::mock(User::class);
        $userToGrant->shouldReceive('getAttribute')->with('id')->andReturn(2);

        // Mock the service to update the user role
        $this->shopServiceMock->shouldReceive('updateUserRoleInShop')
            ->once()
            ->with($this->shop, $userToGrant, Shop::SHOP_USER_ROLE_MANAGER)
            ->andReturn(true);

        // Call the method
        $response = $this->shopController->grantAdminAccess($request, $this->shop, $userToGrant);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['message' => 'Admin access granted successfully'], json_decode($response->getContent(), true));
    }

    /**
     * Test revokeAdminAccess method
     */
    public function test_revoke_admin_access()
    {
        // Create a mock request
        $request = Mockery::mock(Request::class);

        // Create a mock user
        $userToRevoke = Mockery::mock(User::class);
        $userToRevoke->shouldReceive('getAttribute')->with('id')->andReturn(2);

        // Mock the service to update the user role
        $this->shopServiceMock->shouldReceive('updateUserRoleInShop')
            ->once()
            ->with($this->shop, $userToRevoke, Shop::SHOP_USER_ROLE_CUSTOMER)
            ->andReturn(true);

        // Call the method
        $response = $this->shopController->revokeAdminAccess($request, $this->shop, $userToRevoke);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['message' => 'Admin access revoked successfully'], json_decode($response->getContent(), true));
    }

    /**
     * Test userIsShopAdmin method
     */
    public function test_user_is_shop_admin()
    {
        // Create a mock user
        $userToCheck = Mockery::mock(User::class);
        $userToCheck->shouldReceive('getAttribute')->with('id')->andReturn(2);

        // Mock the service to check admin status
        $this->shopServiceMock->shouldReceive('userIsShopAdmin')
            ->once()
            ->with($this->shop, $userToCheck)
            ->andReturn(true);

        // Call the method
        $response = $this->shopController->userIsShopAdmin($this->shop, $userToCheck);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['is_admin' => true], json_decode($response->getContent(), true));
    }

    /**
     * Test toggleState method
     */
    public function test_toggle_state()
    {
        // Mock Auth facade
        Auth::shouldReceive('user')->andReturn($this->user);

        // Mock the service to toggle the state
        $this->shopServiceMock->shouldReceive('toggleState')
            ->once()
            ->with($this->user, 1, true)
            ->andReturn($this->shop);

        // Call the method
        $response = $this->shopController->toggleState(1, 1);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'message' => 'Shop state updated successfully',
            'state' => true
        ], json_decode($response->getContent(), true));
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

        // Mock the service to return rules
        $this->shopServiceMock->shouldReceive('getShopRules')
            ->once()
            ->with(1)
            ->andReturn($rules);

        // Call the method
        $response = $this->shopController->getShopRules(1);

        // Assert response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($rules, json_decode($response->getContent(), true));
    }
}
