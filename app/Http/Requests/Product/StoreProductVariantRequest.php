<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('variants.create');
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_name' => ['required_with:attributes', 'string', 'max:255'],
            'attributes.*.attribute_value' => ['required_with:attributes', 'string', 'max:255'],
        ];
    }
}
