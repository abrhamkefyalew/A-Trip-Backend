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
                'sometimes', 'email', Rule::unique('admins')->ignore($this->admin->id),
            ],
            'password' => [
                'sometimes', 'min:8', 'confirmed',
            ],
            'phone_number' => [
                'sometimes', 'numeric',  Rule::unique('admins')->ignore($this->admin->id),
            ],
            
            
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',



            'admin_profile_image' => [
                'sometimes',       // this should be sometimes abrham check
                'image',
                'max:3072',
            ],

            'admin_profile_image_remove' => [
                'sometimes', 'boolean',
            ],

        ];
    }
}
