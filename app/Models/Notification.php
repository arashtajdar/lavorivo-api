<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    CONST NOTIFICATION_TYPE_SYSTEM = 'system';
    CONST NOTIFICATION_TYPE_SHOP_ADDED_TO_USER = 'shopAddedToUser';
    CONST NOTIFICATION_TYPE_NEW_EMPLOYEE_CREATED = 'newEmployeeCreated';


    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'message', 'data', 'is_read'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
