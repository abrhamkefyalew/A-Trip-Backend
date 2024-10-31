<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Role::class);
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
                'required', 
                'unique:roles,title',
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
