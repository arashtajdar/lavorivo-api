<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateDay extends Model
{
    use HasFactory;

    protected $fillable = ['template_id', 'day_of_week', 'shift_data'];

    protected $casts = [
        'shift_data' => 'array',
    ];
    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
