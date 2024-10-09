<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractDetailRequest extends FormRequest
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
            'contract_id' => 'sometimes|integer|exists:contracts,id',
            'vehicle_name_id' => 'sometimes|integer|exists:vehicle_names,id',

            'with_driver' => [
                'sometimes', 'boolean',
            ],
            'with_fuel' => [
                'sometimes', 'boolean',
            ],
            'periodic' => [
                'sometimes', 'boolean',
            ],
            'price_contract' => 'sometimes|integer|between:0,9999999',
            'price_vehicle_payment' => 'sometimes|integer|between:0,9999999',

            // Specifies that the numeric value of the field must be within the range of 0 to 15, inclusive. This means that the value of tax can be any numeric value between 0 and 15, including both 0 and 15.
            'tax' => 'sometimes|numeric|between:0,15',

            // TODO // "is_available" column in CONTRACT_DETAILs table should NOT be update separately,  // we ONLY update "is_available" when Terminating or UnTerminating the PARENT CONTRACT
            // 'is_available' => [
            //     'sometimes', 'boolean',
            // ],
        ];
    }
}
