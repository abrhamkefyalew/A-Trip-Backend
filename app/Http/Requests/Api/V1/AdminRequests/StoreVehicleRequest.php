<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('create', Vehicle::class);
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
            'vehicle_name_id' => 'required|integer|exists:vehicle_names,id',
            'supplier_id' => 'sometimes|integer|exists:suppliers,id',
            'driver_id' => [
                'sometimes', 
                'integer', 
                'exists:drivers,id',            // Rule::exists('drivers'), // also works       // or       // Rule::exists('drivers', 'id'),
                'unique:vehicles,driver_id',    // Rule::unique('vehicles'), // also works      // or       // Rule::unique('vehicles', 'driver_id'),
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
                'required', 'string', Rule::in([Vehicle::VEHICLE_NOT_AVAILABLE, Vehicle::VEHICLE_AVAILABLE, Vehicle::VEHICLE_ON_TRIP]),
            ],
            'with_driver' => [
                'required', 'boolean',
            ],


            // the car location is used to know if the vehicle is available or found in what location
            // should i also add latitude and longitude
            // so the question is, should they be required or sometimes // check first // ask samson
            'country' => [
                'sometimes', 'string',
            ],
            'city' => [
                'sometimes', 'string',
            ],

            // should the following medias be required or sometimes
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

            // since it is Storing vehicle for the first time there is no need to remove any image, so we do NOT need remove_image
            // // also when doing remove image try to do it for specific collection
            // 'remove_image' => [
            //     'sometimes', 'boolean',
            // ],
        ];
    }
}
