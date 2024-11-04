<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->driver);
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
            // email should NOT be updated , Because it is being used for login currently
            // 'email' => [
            //     'sometimes', 'email', Rule::unique('drivers')->ignore($this->driver->id),
            // ],
            'phone_number' => [
                'sometimes', 'numeric', Rule::unique('drivers')->ignore($this->driver->id),
            ],
            'is_active' => [
                'sometimes', 'boolean',
            ],


            // this column can ONLY be Set by the SUPER_ADMIN, 
            // if Driver is registering himself , he can NOT send the is_approved field
            // there should be separate endpoint to update this
            // 'is_approved' => [
            //     'sometimes', 'boolean',
            // ],



            // password should NOT be updated here
            // there is a different procedure for resetting password called=(forget-password and reset-password)
            // 'password' => [
            //     'sometimes', 'min:8', 'confirmed',
            // ],


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


            // MEDIA ADD
            
            'driver_license_front_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'driver_license_back_image' => [
                'sometimes',
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


            // MEDIA REMOVE
            
            // GOOD IDEA = ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection
            

            // BAD IDEA = when doing remove image try to do it for specific collection
            'driver_license_front_image_remove' => [
                'sometimes', 'boolean',
            ],
            'driver_license_back_image_remove' => [
                'sometimes', 'boolean',
            ],
            'driver_id_front_image_remove' => [
                'sometimes', 'boolean',
            ],
            'driver_id_back_image_remove' => [
                'sometimes', 'boolean',
            ],
            'driver_profile_image_remove' => [
                'sometimes', 'boolean',
            ],


        ];
    }
}
