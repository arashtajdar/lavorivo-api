<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert subscription data
        DB::table('subscriptions')->insert([
            [
                'id' => 1,
                'product_id' => null,
                'name' => 'BASIC',
                'category' => 1,
                'price' => 0.00,
                'discounted_price' => 0.00,
                'image' => 'basic.jpg',
                'maximum_shops' => 5,
                'maximum_employees' => 10,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'product_id' => 'prod_Rmgd2KbFt7MaHx',
                'name' => 'PREMIUM',
                'category' => 1,
                'price' => 19.99,
                'discounted_price' => 4.99,
                'image' => 'premium.jpg',
                'maximum_shops' => 5,
                'maximum_employees' => 50,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'product_id' => null,
                'name' => 'GOLD',
                'category' => 1,
                'price' => 149.99,
                'discounted_price' => 99.99,
                'image' => 'gold.jpg',
                'maximum_shops' => 100,
                'maximum_employees' => 500,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'product_id' => null,
                'name' => 'PREMIUM',
                'category' => 2,
                'price' => 199.99,
                'discounted_price' => 119.99,
                'image' => 'premium.jpg',
                'maximum_shops' => 5,
                'maximum_employees' => 50,
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'product_id' => null,
                'name' => 'GOLD',
                'category' => 2,
                'price' => 1499.99,
                'discounted_price' => 1299.99,
                'image' => 'gold.jpg',
                'maximum_shops' => 100,
                'maximum_employees' => 500,
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create the user
        $user = User::create([
            'name' => 'Arash Tajdar',
            'email' => 'arash.tajdar@gmail.com',
            'password' => Hash::make('1234567890'),
            'role' => User::USER_ROLE_MANAGER,
            'subscription_id' => 1, // BASIC subscription
            'email_verified_at' => now(),
        ]);

        // Create a shop for the user
        $shop = Shop::create([
            'name' => 'My First Shop',
            'location' => 'Default Location',
            'owner' => $user->id,
            'state' => true,
        ]);
    }
}
