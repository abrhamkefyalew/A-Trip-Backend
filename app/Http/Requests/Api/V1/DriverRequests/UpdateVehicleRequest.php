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
