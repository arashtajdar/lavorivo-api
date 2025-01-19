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
    // Add constants for status
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    // Accessor for human-readable status
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }
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
