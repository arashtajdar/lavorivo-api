<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\ShopService;
use App\Repositories\ShopRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $shop;
    protected $shopServiceMock;
    protected $shopRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Create a test shop
        $this->shop = Shop::factory()->create([
            'owner' => $this->user->id
        ]);

        // Mock the ShopService and ShopRepository
        $this->shopServiceMock = Mockery::mock(ShopService::class);
        $this->shopRepositoryMock = Mockery::mock(ShopRepository::class);

        $this->app->instance(ShopService::class, $this->shopServiceMock);
        $this->app->instance(ShopRepository::class, $this->shopRepositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test store method creates a new shop
     */
    public function test_store_creates_new_shop()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Prepare test data
        $shopData = [
            'name' => $this->faker->company,
            'description' => $this->faker->sentence,
            'address' => $this->faker->address,
        ];

        // Mock the service response
        $this->shopServiceMock->shouldReceive('createShop')
            ->once()
            ->with(Mockery::any(), $this->user)
            ->andReturn($this->shop);

        // Make the request
        $response = $this->postJson('/api/shops', $shopData);

        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'address',
                'owner',
                'created_at',
                'updated_at'
            ]);
    }

    /**
     * Test update method updates an existing shop
     */
    public function test_update_modifies_existing_shop()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Prepare test data
        $updateData = [
            'name' => $this->faker->company,
            'description' => $this->faker->sentence,
        ];

        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with($this->shop->id)
            ->andReturn($this->shop);

        // Mock the shop update
        $this->shop->shouldReceive('update')
            ->once()
            ->with($updateData)
            ->andReturn(true);

        // Make the request
        $response = $this->putJson("/api/shops/{$this->shop->id}", $updateData);

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'address',
                'owner',
                'created_at',
                'updated_at'
            ]);
    }

    /**
     * Test destroy method deletes a shop
     */
    public function test_destroy_deletes_shop()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Mock the repository to delete the shop
        $this->shopRepositoryMock->shouldReceive('deleteById')
            ->once()
            ->with($this->shop->id)
            ->andReturn(true);

        // Make the request
        $response = $this->deleteJson("/api/shops/{$this->shop->id}");

        // Assert response
        $response->assertStatus(200)
            ->assertJson(['message' => 'Shop deleted']);
    }

}
