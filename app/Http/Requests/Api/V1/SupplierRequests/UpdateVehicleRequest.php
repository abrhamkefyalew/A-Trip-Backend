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
            'vehicle_name_id' => 'sometimes|integer|exists:vehicle_names,id',
            'driver_id' => [
                'sometimes', 
                'nullable',
                'integer', 
                'exists:drivers,id',            // Rule::exists('drivers'), // also works       // or       // Rule::exists('drivers', 'id'),
                Rule::unique('vehicles')->ignore($this->vehicle->id),
            ],
            

            'vehicle_name' => [
                'sometimes', 'string',
            ],
            'vehicle_description' => [
                'sometimes', 'string',
            ],
            'vehicle_model' => [
                'sometimes', 'string',
            ],
            'plate_number' => [
                'sometimes', 'string', Rule::unique('vehicles'),
            ],
            'year' => [
                'sometimes', 'string',
            ],
            'is_available' => [
                'sometimes', 'string', Rule::in([Vehicle::VEHICLE_NOT_AVAILABLE, Vehicle::VEHICLE_AVAILABLE, Vehicle::VEHICLE_ON_TRIP]),
            ],
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


            // TODO 
            // 'vehicle_libre_image' => [
            //     'sometimes',
            //     'image',
            //     'max:3072',
            // ],
            // 'vehicle_third_person_image' => [
            //     'sometimes',
            //     'image',
            //     'max:3072',
            // ],
            // 'vehicle_power_of_attorney_image' => [
            //     'sometimes',
            //     'image',
            //     'max:3072',
            // ],
            // 'vehicle_profile_image' => [
            //     'sometimes',
            //     'image',
            //     'max:3072',
            // ],

            // // also when doing remove image try to do it for specific collection
            // 'remove_image' => [
            //     'sometimes', 'boolean',
            // ],
        ];
    }
}
