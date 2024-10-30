<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->admin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //

            'first_name' => [
                'sometimes', 'string', 'regex:/^\S*$/u', 'alpha',
            ],
            'last_name' => [
                'sometimes', 'string', 'regex:/^\S*$/u', 'alpha',
            ],
            'email' => [
                'sometimes', 'email', Rule::unique('admins'),
            ],
            'password' => [
                'sometimes', 'min:8', 'confirmed',
            ],
            'phone_number' => [
                'nullable', 'numeric',  Rule::unique('admins'),
            ],
            'profile_image' => [
                'image',
                'max:3072',
            ],
            'remove_image' => [
                'boolean',
            ],
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',

        ];
    }
}
