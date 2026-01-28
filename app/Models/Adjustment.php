<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $fillable =[
        "reference_no", 
        "warehouse_id", 
        "document", 
        "total_qty", 
        "item",
        "note",
        "type"
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    public function productAdjustments()
    {
        return $this->hasMany(ProductAdjustment::class);
    }
}
