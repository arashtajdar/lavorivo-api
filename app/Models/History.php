<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $table = 'history';

    protected $fillable = [
        'user_id',
        'action_type',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
    ];

    // Action Types
    const ADD_EMPLOYEE = 1;
    const REMOVE_EMPLOYEE = 2;
    const ADD_SHOP = 3;
    const REMOVE_SHOP = 4;
    const UPDATE_SHOP = 5;
    const ADD_SHIFT = 6;
    const REMOVE_SHIFT = 7;
    const UPDATE_SHIFT = 8;
    const REQUEST_SHIFT_SWAP = 9;
    const ACCEPT_SHIFT_SWAP = 10;
    const REJECT_SHIFT_SWAP = 11;
    const REQUEST_OFF_DAY = 12;
    const APPROVE_OFF_DAY = 13;
    const REJECT_OFF_DAY = 14;
    const USER_SUBSCRIBED = 15;
    const USER_CANCELLED_SUBSCRIPTION = 16;
    const USER_UPDATED_PROFILE = 17;
    const USER_CHANGED_PASSWORD = 18;
    const USER_DELETED_ACCOUNT = 19;
    const USER_LOGIN = 20;
    const USER_FORGET_PASSWORD_REQUEST = 21;
    const RULE_ADDED = 22;
    const RULE_REMOVED = 23;
    const ADD_LABEL = 24;
    const UPDATE_LABEL = 25;
    const REMOVE_LABEL = 26;
    const ADD_USER_TO_SHOP = 27;
    const REMOVE_USER_FROM_SHOP = 28;
    const PURCHASE_SUCCESS = 29;
}
