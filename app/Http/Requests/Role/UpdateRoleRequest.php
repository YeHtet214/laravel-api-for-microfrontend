<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')->id ?? $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $roleId],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug,' . $roleId],
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ];
    }
}
