<?php

namespace App\Http\Requests\Api\V1\DriverRequests;

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
            // this update request is = to change VEHICLE_AVAILABLE, VEHICLE_NOT_AVAILABLE, VEHICLE_ON_TRIP, when a trip is completed, when vehicle is not available and when vehicle starts a trip - and such
            // also to update only some vehicle attributes that the driver is allowed to update

            
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
                    // 1. if the vehicle is not in orders table     - or -    2. or even if the vehicle is in orders table:- the order (orders) that owns the vehicle should NOT be STARTED,
                            //
                            // (PENDING order with vehicle_id is less likely, and should NOT exist)
                            // if order is ACCEPTED (SET), and if vehicle is_available is sent , what should i do, check abrham samson // ask samson
                            //
            // 'is_available' => [
            //     'sometimes', 'string', Rule::in([Vehicle::VEHICLE_NOT_AVAILABLE, Vehicle::VEHICLE_AVAILABLE, Vehicle::VEHICLE_ON_TRIP]),
            // ],

            // since the driver is holding the vehicle moving it around in different locations, he too can update the location of the vehicle
            'country' => [
                'sometimes', 'string',
            ],
            'city' => [
                'sometimes', 'string',
            ],

        ];

    }
}
