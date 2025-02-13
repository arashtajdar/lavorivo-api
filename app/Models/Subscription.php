<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'price', 'discounted_price'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
