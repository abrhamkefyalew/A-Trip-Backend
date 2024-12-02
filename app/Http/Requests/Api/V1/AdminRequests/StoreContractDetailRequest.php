<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\ContractDetail;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ContractDetail::class);
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
            'contract_id' => 'required|integer|exists:contracts,id',
            'vehicle_name_id' => 'required|integer|exists:vehicle_names,id',

            'with_driver' => [
                'required', 'boolean',
            ],
            'with_fuel' => [
                'required', 'boolean',
            ],
            'periodic' => [
                'sometimes', 'boolean',
            ],
            'price_contract' => 'required|integer|between:0,9999999',
            'price_vehicle_payment' => 'required|integer|between:0,9999999',

            // Specifies that the numeric value of the field must be within the range of 0 to 15, inclusive. This means that the value of tax can be any numeric value between 0 and 15, including both 0 and 15.
            'tax' => 'sometimes|nullable|numeric|between:0,15',
            'price_fuel_payment_constant' => 'sometimes|numeric|between:0,9999999.99',

            // is_available = should be not be sent at ContractDetail store for the first time, it is "1" by default in the controller
            // 'is_available' => [
            //     'sometimes', 'boolean',
            // ],

        ];
    }
}
