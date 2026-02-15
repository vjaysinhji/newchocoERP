<?php

namespace App\Http\Requests\Subcategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'subcate_banner_img' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:1024',
            'name_english' => 'required|string|max:255',
            'name_arabic' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description_english' => 'nullable|string|max:5000',
            'description_arabic' => 'nullable|string|max:5000',
        ];
    }
}
