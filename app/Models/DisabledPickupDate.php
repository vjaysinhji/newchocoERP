<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisabledPickupDate extends Model
{
    protected $fillable = [
        'date',
        'reason_en',
        'reason_ar',
        'sort_order',
        'type_name',
        'is_active',
    ];
}

