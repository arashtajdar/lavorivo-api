<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSwapRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', // Include shop_id
        'shift_label_id',
        'shift_date',
        'requester_id',
        'requested_id',
        'status',
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function shiftLabel()
    {
        return $this->belongsTo(ShiftLabel::class, 'shift_label_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function requested()
    {
        return $this->belongsTo(User::class, 'requested_id');
    }
}
