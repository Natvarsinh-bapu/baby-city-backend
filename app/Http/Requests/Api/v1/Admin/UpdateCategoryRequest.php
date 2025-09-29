<?php

namespace App\Http\Requests\Api\v1\Admin;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id'); // assumes route parameter is {id}

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // max 10 MB
            'sub_categories' => 'nullable|array',
            'sub_categories.*.id' => 'nullable|exists:sub_categories,id',
            'sub_categories.*.name' => 'required|string|max:255',
            'sub_categories.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // max 10 MB
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->customJson(false, [], [], $validator->errors()->all(), '', 400)
        );
    }
}
