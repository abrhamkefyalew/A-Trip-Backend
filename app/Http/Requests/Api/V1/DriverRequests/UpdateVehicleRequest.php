<?php

namespace App\Http\Requests\Api\V1\DriverRequests;

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
            // this to change VEHICLE_AVAILABLE, VEHICLE_NOT_AVAILABLE, VEHICLE_ON_TRIP, when a trip is completed, when vehicle is not available and when vehicle starts a trip - and such
        ];
    }
}
