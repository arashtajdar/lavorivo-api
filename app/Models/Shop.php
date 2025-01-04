<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function usersWithRole()
    {
        return $this->belongsToMany(User::class, 'shop_user')
            ->withPivot('role'); // Include the 'role' column from the pivot table
    }
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/y \[H:i\]');
    }
    public function managers()
    {
        return $this->belongsToMany(User::class, 'shop_user', 'shop_id', 'user_id')
            ->wherePivot('role', self::SHOP_USER_ROLE_MANAGER)
            ->withPivot('role');
    }


}
