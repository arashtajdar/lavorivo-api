<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    const DEFAULT_SUBSCRIPTION_ID = 1;

    const SUBSCRIPTION_CATEGORIES = [
        1 => 'MONTHLY',
        2 => 'ANNUALLY',
    ];

    use HasFactory;

    protected $fillable = ['name', 'category', 'price', 'discounted_price', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
