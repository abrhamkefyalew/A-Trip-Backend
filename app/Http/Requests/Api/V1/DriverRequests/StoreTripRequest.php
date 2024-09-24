<?php

namespace App\Http\Requests\Api\V1\DriverRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
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
            'order_id' => 'required|integer|exists:orders,id',
            // driver_id // i will get it from the token
            'organization_user_id' => 'required|integer|exists:organization_users,id',

            'start_dashboard' => 'required|integer|min:0|max:9223372036854775807',

            'source' => 'required|string',
            'destination' => 'sometimes|nullable|string',

            'trip_date' => 'sometimes|nullable|date|date_format:Y-m-d',

            'trip_description' => 'sometimes|nullable|string',

        ];
    }
}
