<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
