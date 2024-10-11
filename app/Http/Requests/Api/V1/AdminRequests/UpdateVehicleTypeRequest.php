<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('update', $this->vehicleType);
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
            'vehicle_type_name' => [
                'sometimes', 
                'string', 
                Rule::unique('vehicle_types')->ignore($this->vehicleType->id),
            ],  // should NOT be nullable // since name can NOT be updated to be null
            'vehicle_type_description' => ['sometimes', 'nullable', 'string'],  // should be nullable // since description can be updated to be empty or null
        ];
    }
}
