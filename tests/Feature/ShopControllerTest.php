<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $shop;
    protected $shopService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user with employer role
        $this->user = User::factory()->create([
            'employer' => null,
        ]);
        
        // Create a shop owned by the user
        $this->shop = Shop::factory()->create([
            'owner' => $this->user->id,
            'name' => 'Test Shop',
            'location' => 'Test Location',
        ]);
        
        // Mock the ShopService
        $this->shopService = Mockery::mock(ShopService::class);
        $this->app->instance(ShopService::class, $this->shopService);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test creating a new shop
     *
     * @return void
     */
    public function test_store_shop()
    {
        // Mock the ShopService to return a shop
        $newShop = Shop::factory()->make([
            'id' => 123,
            'owner' => $this->user->id,
            'name' => 'New Shop',
            'location' => 'New Location',
        ]);
        
        $this->shopService->shouldReceive('createShop')
            ->once()
            ->andReturn($newShop);
        
        $shopData = [
            'name' => 'New Shop',
            'location' => 'New Location',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/shops', $shopData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'location',
                'owner',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'name' => 'New Shop',
                'location' => 'New Location',
                'owner' => $this->user->id,
            ]);
    }

    /**
     * Test adding a user to a shop
     *
     * @return void
     */
    public function test_add_user_to_shop()
    {
        // Create another user to add to the shop
        $employee = User::factory()->create();
        
        // Mock the ShopService to return a success response
        $this->shopService->shouldReceive('addUserToShop')
            ->once()
            ->andReturn(response()->json(['message' => 'User added to shop successfully.'], 200));

        $response = $this->actingAs($this->user)
            ->postJson("/api/shops/{$this->shop->id}/users", [
                'user_id' => $employee->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User added to shop successfully.',
            ]);
    }
    
    /**
     * Test toggling shop state
     *
     * @return void
     */
    public function test_toggle_shop_state()
    {
        // Mock the ShopService to return a shop with updated state
        $updatedShop = Shop::factory()->make([
            'id' => $this->shop->id,
            'owner' => $this->user->id,
            'name' => 'Test Shop',
            'location' => 'Test Location',
            'state' => true,
        ]);
        
        $this->shopService->shouldReceive('toggleState')
            ->once()
            ->with($this->user, $this->shop->id, true)
            ->andReturn($updatedShop);
        
        $response = $this->actingAs($this->user)
            ->patchJson("/api/shops/{$this->shop->id}/toggle-state/1");
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Shop state updated successfully',
                'state' => true,
            ]);
    }
} 