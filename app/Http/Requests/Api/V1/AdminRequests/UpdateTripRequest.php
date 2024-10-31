<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->trip);
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
            'driver_id' => [
                'sometimes', 
                'integer', 
                'exists:drivers,id',            // Rule::exists('drivers'), // also works       // or       // Rule::exists('drivers', 'id'),
            ],
            
            'organization_user_id' => 'sometimes|integer|exists:organization_users,id',
            
            'start_dashboard' => 'sometimes|integer|min:0|max:9223372036854775807',
            'end_dashboard' => 'sometimes|integer|min:0|max:9223372036854775807',

            'source' => 'sometimes|string',
            'destination' => 'sometimes|string',

            'trip_date' => 'sometimes|date|date_format:Y-m-d',

            'trip_description' => 'sometimes|nullable|string',
        ];
    }
}
