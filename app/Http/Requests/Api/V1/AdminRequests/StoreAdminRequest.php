<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Admin;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Admin::class);
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
                'required', 'string', 'regex:/^\S*$/u', 'alpha',
            ],
            'last_name' => [
                'required', 'string', 'regex:/^\S*$/u', 'alpha',
            ],
            'email' => [
                'required', 'email', Rule::unique('admins'),
            ],
            'password' => [
                'required', 'min:8', 'confirmed',
            ],
            'phone_number' => [
                'sometimes', 'numeric',  Rule::unique('admins'),
            ],
            


            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',



            'admin_profile_image' => [
                'required',
                'image',
                'max:3072',
            ],

            // since it is first admin is being stored time we do NOT need this
            // 'admin_profile_image_remove' => [
            //     'sometimes', 'boolean',
            // ],

        ];
    }
}
