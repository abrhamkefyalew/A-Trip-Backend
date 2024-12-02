<?php

namespace App\Http\Requests\Api\V1\SupplierRequests;

use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
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
        return [
            //
            // there should be separate endpoint to update this 
            // 'vehicle_name_id' => 'sometimes|integer|exists:vehicle_names,id',

            'driver_id' => [
                'sometimes', 
                'nullable',
                'integer', 
                'exists:drivers,id',            // Rule::exists('drivers'), // also works       // or       // Rule::exists('drivers', 'id'),
                Rule::unique('vehicles')->ignore($this->vehicle->id),
            ],
            

            'vehicle_name' => [
                'sometimes', 'nullable', 'string',
            ],
            'vehicle_description' => [
                'sometimes', 'nullable', 'string',
            ],
            'vehicle_model' => [
                'sometimes', 'nullable', 'string',
            ],
            'plate_number' => [
                'sometimes', 'string', Rule::unique('vehicles')->ignore($this->vehicle->id),
            ],
            'year' => [
                'sometimes', 'string',
            ],

            // there should be separate endpoint to update this 
                // this update should be allowed only               
                    // 1. if the vehicle is not in orders table     - or -    2. or even if the vehicle is in orders table:- the order (orders) that owns the vehicle should NOT have status = STARTED, to update the below column,
                            //
                            // (PENDING order with vehicle_id is less likely, and should NOT exist)
                            // if order is ACCEPTED (SET), and if vehicle is_available is sent , what should i do, check abrham samson // ask samson
                            //
            // 'is_available' => [
            //     'sometimes', 'string', Rule::in([Vehicle::VEHICLE_NOT_AVAILABLE, Vehicle::VEHICLE_AVAILABLE, Vehicle::VEHICLE_ON_TRIP]),
            // ],

            'with_driver' => [
                'sometimes', 'boolean',
            ],

            // the following vehicle bank information are nullable and sometimes BECAUSE of the following reason
                     // REASON 1- since ADIAMAT might have their own vehicles (that means adiamat will not pay adiamat themselves for their own vehicles), this should be nullable
                     // TODO // please check this = both of them must be sent    - or -     or none of them should be sent,     // so please check this while Store Vehicle and Update Vehicle
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

            'vehicle_libre_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'vehicle_third_person_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'vehicle_power_of_attorney_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],
            'vehicle_profile_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],


            // MEDIA REMOVE
            
            // GOOD IDEA = ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection
            

            // BAD IDEA = when doing remove image try to do it for specific collection
            'vehicle_libre_image_remove' => [
                'sometimes', 'boolean',
            ],
            'vehicle_third_person_image_remove' => [
                'sometimes', 'boolean',
            ],
            'vehicle_power_of_attorney_image_remove' => [
                'sometimes', 'boolean',
            ],
            'vehicle_profile_image_remove' => [
                'sometimes', 'boolean',
            ],


        ];
    }
}
