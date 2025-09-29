<?php

namespace App\Http\Requests\Api\v1\Admin;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AddCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // max 10 MB
            'sub_categories' => 'nullable|array',
            'sub_categories.*.name' => 'string|max:255',
            'sub_categories.*.image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // max 10 MB
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->customJson(false, [], [], $validator->errors()->all(), '', 400)
        );
    }
}
