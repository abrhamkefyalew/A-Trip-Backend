<?php

namespace App\Http\Requests\Api\V1\OrganizationUserRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            // multiple orders can be sent at once 
            // i will put similar order_code in OrderController = for those multiple orders that are sent at once

            '*.contract_detail_id' => 'required|integer|exists:contract_details,id', // this contract_detail_id should be owned by the organization that the order requester belongs in
            // '*.vehicle_name_id' => 'required|integer|exists:vehicle_names,id', // this SHOULD NOT BE SENT by the order maker //  i should get from the contract_detail_id, 
            '*.start_date' => 'required|date|date_format:Y-m-d', 
            '*.end_date' => 'required|date|date_format:Y-m-d', 
            // the start_date and end_date must be <= the Contract end_date
            // the start_date and end_date must be >= the Contract start_date
            // start_date must be >= todays date

            '*.start_location' => 'nullable|string',
            '*.end_location' => 'nullable|string',

            '*.start_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            '*.start_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            '*.end_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            '*.end_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            '*.order_description' => 'sometimes|nullable|string',

        ];
    }
}
