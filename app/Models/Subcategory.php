<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = [
        'category_id',
        'subcate_banner_img',
        'image',
        'name_english',
        'name_arabic',
        'slug',
        'show_in_menu',
        'menu_sort_order',
        'sort_order',
        'description_english',
        'description_arabic',
    ];

    protected $casts = [
        'show_in_menu' => 'boolean',
        'sort_order' => 'integer',
        'menu_sort_order' => 'integer',
    ];

    /**
     * Subcategory belongs to a product category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
