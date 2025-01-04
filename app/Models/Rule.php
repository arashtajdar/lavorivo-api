<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasFactory;

    const RULE_TYPE_EXCLUDE_DAYS = "exclude_days";
    const RULE_TYPE_EXCLUDE_LABELS = "exclude_label";
    CONST RULE_WEEK_DAYS =  ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    CONST RULE_EXCLUDE_DAYS_MONDAY = 1;
    CONST RULE_EXCLUDE_DAYS_TUESDAY = 2;
    CONST RULE_EXCLUDE_DAYS_WEDNESDAY = 3;
    CONST RULE_EXCLUDE_DAYS_THURSDAY = 4;
    CONST RULE_EXCLUDE_DAYS_FRIDAY = 5;
    CONST RULE_EXCLUDE_DAYS_SATURDAY = 6;
    CONST RULE_EXCLUDE_DAYS_SUNDAY = 7;

    protected $fillable = ['employee_id', 'rule_type', 'rule_data', 'shop_id'];

    protected $casts = [
        'rule_data' => 'array', // Automatically cast JSON to an array
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

}
