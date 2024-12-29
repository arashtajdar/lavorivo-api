<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    const SHOP_USER_ROLE_CUSTOMER = 3;
    const SHOP_USER_ROLE_MANAGER = 2;
    use HasFactory;

    protected $fillable = ['name', 'location', 'owner'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'shop_user');
    }
    public function shiftLabels()
    {
        return $this->hasMany(ShiftLabel::class);
    }




}
