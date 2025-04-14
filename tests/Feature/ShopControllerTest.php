<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $shop;

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
    }

    /**
     * Test creating a new shop
     *
     * @return void
     */
    public function test_store_shop()
    {
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

        $this->assertDatabaseHas('shops', [
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

        $response = $this->actingAs($this->user)
            ->postJson("/api/shops/{$this->shop->id}/users", [
                'user_id' => $employee->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User added to shop successfully.',
            ]);

        $this->assertDatabaseHas('shop_user', [
            'shop_id' => $this->shop->id,
            'user_id' => $employee->id,
        ]);
    }
} 