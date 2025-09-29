<?php

namespace App\Http\Requests\Api\v1\Admin;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AddProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Info
            'name' => 'required|string|max:255|unique:products,name',
            'slug' => 'nullable|string|unique:products,slug',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',

            // Pricing
            'price' => 'required|numeric',
            'sale_price' => 'nullable|numeric|lt:price',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',

            // Inventory
            'stock_quantity' => 'nullable|integer|min:0',
            'in_stock' => 'nullable',
            'manage_stock' => 'nullable',

            // Images
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // max 10 MB
            'gallery' => 'nullable|array',
            'gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // max 10 MB

            // Flags
            'featured' => 'nullable',
            'active' => 'nullable',

            // Attributes & Variants (dynamic key-value)
            'attributes' => 'nullable|array',
            'attributes.*.key' => 'required_with:attributes|string|max:255',
            'attributes.*.value' => 'required_with:attributes|string|max:255',

            'variants' => 'nullable|array',
            'variants.*.key' => 'required_with:variants|string|max:255',
            'variants.*.value' => 'required_with:variants|string|max:255',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->customJson(false, [], [], $validator->errors()->all(), '', 400)
        );
    }
}
