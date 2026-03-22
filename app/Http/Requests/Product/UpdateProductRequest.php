<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('products.update');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($this->product),
            ],
            'description' => ['nullable', 'string'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->product),
            ],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'has_variants' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
