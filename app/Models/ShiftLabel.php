<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftLabel extends Model
{
    use HasFactory;

    protected $fillable = ['shop_id', 'user_id', 'label', 'default_duration_minutes', 'applicable_days'];
    protected $casts = [
        'applicable_days' => 'array',
    ];
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
