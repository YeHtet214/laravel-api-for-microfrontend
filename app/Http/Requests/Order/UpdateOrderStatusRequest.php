<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('orders.status.update');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,confirmed,processing,completed,cancelled'],
        ];
    }
}
