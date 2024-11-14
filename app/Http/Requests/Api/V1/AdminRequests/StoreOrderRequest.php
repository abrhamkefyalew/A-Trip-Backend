<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
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
            'organization_id' => 'required|integer|exists:organizations,id',



            
            // multiple orders can be sent at once 
            // i will put similar order_code in OrderController = for those multiple orders that are sent at once
            'orders' => 'required|array',

            'orders.*' => 'sometimes',


            'orders.*.contract_detail_id' => 'required|integer|exists:contract_details,id', // this contract_detail_id should be owned by the organization that the order requester belongs in
            // 'orders.*.vehicle_name_id' => 'required|integer|exists:vehicle_names,id', // this SHOULD NOT BE SENT by the order maker //  i should get from the contract_detail_id, 
            'orders.*.start_date' => 'required|date|date_format:Y-m-d', 
            'orders.*.end_date' => 'required|date|date_format:Y-m-d', 
            
            'orders.*.start_location' => 'nullable|string',
            'orders.*.end_location' => 'nullable|string',

            'orders.*.start_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'orders.*.start_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'orders.*.end_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'orders.*.end_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            'orders.*.order_description' => 'sometimes|nullable|string',

        ];
    }
}
