<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('create', ContractDetail::class);
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
                'sometimes', 'nullable', 'boolean',
            ],
            'price_contract' => 'required|numeric|between:0,9999999.99',
            'price_vehicle_payment' => 'required|numeric|between:0,9999999.99',

        ];
    }
}
