<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->role);
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

            'title' => [
                'sometimes',
                Rule::unique('roles')->ignore($this->role->id),
            ],


            'permission_ids' => [
                'sometimes',
                'array',
            ],
            'permission_ids.*' => [
                'exists:permissions,id',
            ],
            

        ];
    }
}
