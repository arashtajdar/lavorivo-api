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

    /**
     * Test addUserToShop method adds a user to a shop
     */
    public function test_add_user_to_shop()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Create another user to add to the shop
        $newUser = User::factory()->create();

        // Prepare test data
        $requestData = [
            'user_id' => $newUser->id,
            'role' => 'customer'
        ];

        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with($this->shop->id)
            ->andReturn($this->shop);

        // Mock the service to add the user
        $this->shopServiceMock->shouldReceive('addUserToShop')
            ->once()
            ->with($this->shop, $newUser)
            ->andReturn(['message' => 'User added to shop successfully']);

        // Make the request
        $response = $this->postJson("/api/shops/{$this->shop->id}/users", $requestData);

        // Assert response
        $response->assertStatus(200)
            ->assertJson(['message' => 'User added to shop successfully']);
    }

    /**
     * Test removeUserFromShop method removes a user from a shop
     */
    public function test_remove_user_from_shop()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Create another user to remove from the shop
        $userToRemove = User::factory()->create();

        // Mock the repository to find the shop
        $this->shopRepositoryMock->shouldReceive('findById')
            ->once()
            ->with($this->shop->id)
            ->andReturn($this->shop);

        // Mock the service to remove the user
        $this->shopServiceMock->shouldReceive('removeUserFromShop')
            ->once()
            ->with($this->shop, $userToRemove)
            ->andReturn(['message' => 'User removed from shop successfully']);

        // Make the request
        $response = $this->deleteJson("/api/shops/{$this->shop->id}/users/{$userToRemove->id}");

        // Assert response
        $response->assertStatus(200)
            ->assertJson(['message' => 'User removed from shop successfully']);
    }

    /**
     * Test shopsByEmployer method returns shops for the authenticated employer
     */
    public function test_shops_by_employer()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Mock the service to return shops
        $this->shopServiceMock->shouldReceive('getShopsByEmployer')
            ->once()
            ->with($this->user)
            ->andReturn([$this->shop]);

        // Make the request
        $response = $this->getJson('/api/shops/employer');

        // Assert response
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonStructure([
                [
                    'id',
                    'name',
                    'description',
                    'address',
                    'owner',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test usersByShop method returns users for a specific shop
     */
    public function test_users_by_shop()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Create a user to be in the shop
        $shopUser = User::factory()->create();

        // Mock the service to return users
        $this->shopServiceMock->shouldReceive('getUsersByShop')
            ->once()
            ->with($this->shop)
            ->andReturn([$shopUser]);

        // Make the request
        $response = $this->getJson("/api/shops/{$this->shop->id}/users");

        // Assert response
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonStructure([
                [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test grantAdminAccess method grants admin access to a user
     */
    public function test_grant_admin_access()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Create a user to grant admin access to
        $userToGrant = User::factory()->create();

        // Mock the service to update the user role
        $this->shopServiceMock->shouldReceive('updateUserRoleInShop')
            ->once()
            ->with($this->shop, $userToGrant, Shop::SHOP_USER_ROLE_MANAGER)
            ->andReturn(true);

        // Make the request
        $response = $this->postJson("/api/shops/{$this->shop->id}/users/{$userToGrant->id}/grant-admin");

        // Assert response
        $response->assertStatus(200)
            ->assertJson(['message' => 'Admin access granted successfully']);
    }

    /**
     * Test revokeAdminAccess method revokes admin access from a user
     */
    public function test_revoke_admin_access()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Create a user to revoke admin access from
        $userToRevoke = User::factory()->create();

        // Mock the service to update the user role
        $this->shopServiceMock->shouldReceive('updateUserRoleInShop')
            ->once()
            ->with($this->shop, $userToRevoke, Shop::SHOP_USER_ROLE_CUSTOMER)
            ->andReturn(true);

        // Make the request
        $response = $this->postJson("/api/shops/{$this->shop->id}/users/{$userToRevoke->id}/revoke-admin");

        // Assert response
        $response->assertStatus(200)
            ->assertJson(['message' => 'Admin access revoked successfully']);
    }

    /**
     * Test userIsShopAdmin method checks if a user is an admin of a shop
     */
    public function test_user_is_shop_admin()
    {
        // Authenticate user
        $this->actingAs($this->user);

        // Create a user to check admin status
        $userToCheck = User::factory()->create();

        // Mock the service to check admin status
        $this->shopServiceMock->shouldReceive('userIsShopAdmin')
            ->once()
            ->with($this->shop, $userToCheck)
            ->andReturn(true);

        // Make the request
        $response = $this->getJson("/api/shops/{$this->shop->id}/users/{$userToCheck->id}/is-admin");

        // Assert response
        $response->assertStatus(200)
            ->assertJson(['is_admin' => true]);
    }

}
