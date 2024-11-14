<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Driver;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Driver::class);
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
                'required', 'email', Rule::unique('drivers'),
            ],
            'phone_number' => [
                'required', 'numeric', Rule::unique('drivers'),
            ],
            'is_active' => [
                'sometimes', 'boolean',
            ],


            // this column can ONLY be Set by the SUPER_ADMIN, 
            // if Driver is registering himself , he can NOT send the is_approved field
            'is_approved' => [
                'sometimes', 'boolean',
            ],



            // we are using OTP so this is commented, until further notice
            'password' => [
                'required', 'min:8', 'confirmed',
            ],



            // TODO // please check this = both of them must be sent    - or -     or none of them should be sent,     // so please check this while Store Driver and Update Driver
            'bank_id' =>  'sometimes|nullable|integer|exists:banks,id',
            'bank_account' => [
                'sometimes', 'nullable', 'string',
            ],
            


            'country' => [
                'sometimes', 'string',
            ],
            'city' => [
                'sometimes', 'string',
            ],

            'driver_license_front_image' => [
                'required',
                'image',
                'max:3072',
            ],
            'driver_license_back_image' => [
                'required',
                'image',
                'max:3072',
            ],
            'driver_id_front_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'driver_id_back_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'driver_profile_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],

            // since it is Storing driver for the first time there is no need to remove any image, so we do NOT need remove_image
            // // also when doing remove image try to do it for specific collection
            // 'remove_image' => [
            //     'sometimes', 'boolean',
            // ],

            
        ];
    }
}
