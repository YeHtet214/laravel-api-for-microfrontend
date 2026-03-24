<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('order');
        return $this->user()->hasPermission('orders.update') && $order->isEditable();
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_phone' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
