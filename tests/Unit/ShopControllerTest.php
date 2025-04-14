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

}
