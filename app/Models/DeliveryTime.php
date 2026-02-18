<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTime extends Model
{
    protected $fillable = [
        'from_time',
        'to_time',
        'sort_order',
        'type_name',
        'is_active',
    ];
}

