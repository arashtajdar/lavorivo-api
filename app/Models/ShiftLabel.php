<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftLabel extends Model
{
    protected $fillable = ['shop_id', 'name', 'description', 'created_by'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

