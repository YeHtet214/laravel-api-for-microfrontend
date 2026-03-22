<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('variants.update');
    }

    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'required', 'exists:products,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')->ignore($this->variant),
            ],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_name' => ['required_with:attributes', 'string', 'max:255'],
            'attributes.*.attribute_value' => ['required_with:attributes', 'string', 'max:255'],
        ];
    }
}
