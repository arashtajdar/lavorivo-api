<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOffDay extends Model
{
    const USER_OFF_DAY_STATUS_PENDING = 0;
    const USER_OFF_DAY_STATUS_APPROVED = 1;
    const USER_OFF_DAY_STATUS_REJECTED = 2;

    use HasFactory;

    protected $fillable = ['user_id', 'off_date', 'reason', 'status'];

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
