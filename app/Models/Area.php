<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        'governorate_id',
        'name_en',
        'name_ar',
        'charge',
        'sort_order',
        'is_active',
    ];

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }
}

