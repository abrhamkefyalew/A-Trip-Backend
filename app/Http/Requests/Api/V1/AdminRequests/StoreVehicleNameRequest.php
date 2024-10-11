<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleNameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('create', VehicleName::class);
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
            'vehicle_type_id' => 'required|integer|exists:vehicle_types,id',
            'vehicle_name' => ['required', 'string'],
            'vehicle_description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
