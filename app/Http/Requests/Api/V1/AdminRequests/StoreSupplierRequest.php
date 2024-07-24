<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('create', Supplier::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            
            'first_name' => [
                'required', 'string', 'regex:/^\S*$/u', 'alpha',
            ],
            'last_name' => [
                'required', 'string', 'regex:/^\S*$/u', 'alpha',
            ],
            'email' => [
                'sometimes', 'email', Rule::unique('suppliers'),
            ],
            'phone_number' => [
                'required', 'numeric',  Rule::unique('suppliers'),
            ],
            'is_active' => [
                'sometimes', 'nullable', 'boolean',
            ],
            'is_approved' => [
                'sometimes', 'nullable', 'boolean',
            ],

            // we are using OTP so this is commented, until further notice
            // 'password' => [
            //     'required', 'min:8', 'confirmed',
            // ],

            'country' => [
                'sometimes', 'string',
            ],
            'city' => [
                'sometimes', 'string',
            ],

            'supplier_id_front_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'supplier_id_back_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'supplier_profile_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],

            // since it is Storing supplier for the first time there is no need to remove any image, so we do NOT need remove_image
            // // also when doing remove image try to do it for specific collection
            // 'remove_image' => [
            //     'sometimes', 'boolean',
            // ],


        ];
    }
}
