<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\VehicleType;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', VehicleType::class);
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
            'vehicle_type_name' => ['required', 'string', 'unique:vehicle_types,vehicle_type_name'],
            'vehicle_type_description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
