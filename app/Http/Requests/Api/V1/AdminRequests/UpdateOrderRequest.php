<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Order;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->order);
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

            // order_code = should not be send for update in my opinion // order_code should not be updated

            'organization_id' => 'sometimes|integer|exists:organizations,id',

            'vehicle_id' => 'sometimes|integer|exists:vehicles,id',


            'contract_detail_id' => 'sometimes|integer|exists:contract_details,id', // this contract_detail_id should be owned by the organization that the order requester belongs in
            // 'vehicle_name_id' => 'sometimes|integer|exists:vehicle_names,id', // this SHOULD NOT BE SENT by the order maker //  i should get from the contract_detail_id, 
            'start_date' => 'sometimes|date|date_format:Y-m-d', 
            'end_date' => 'sometimes|date|date_format:Y-m-d', 
            
            'start_location' => 'nullable|string',
            'end_location' => 'nullable|string',

            'start_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'start_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'end_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'end_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            'order_description' => 'sometimes|nullable|string',

            'is_terminated' => [
                'sometimes', 'boolean',
            ],

            'status' => [
                'sometimes', 'string', Rule::in([Order::ORDER_STATUS_PENDING, Order::ORDER_STATUS_SET, Order::ORDER_STATUS_START, Order::ORDER_STATUS_COMPLETE]),
            ],

            // 'pr_status' => [
            //     'sometimes', 'string', Rule::in([Order::ORDER_PR_STARTED, Order::ORDER_PR_COMPLETED, Order::ORDER_PR_PAID, Order::ORDER_PR_TERMINATED]),
            // ],

        ];
    }
}
