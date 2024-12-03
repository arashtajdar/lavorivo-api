<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['shop_id', 'date', 'shift_data'];
    protected $casts = [
        'shift_data' => 'array', // Automatically handle JSON serialization
    ];
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function assignments()
    {
        return $this->hasMany(Schedule::class);
    }
}
