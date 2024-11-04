<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\OrderUser;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', OrderUser::class);
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
            'customer_id' => 'required|integer|exists:customers,id',


            

            // multiple orders can be sent at once 
            // i will put similar order_code in OrderUserController = for those multiple orders that are sent at once
            'orders' => 'required|array',

            'orders.*' => 'sometimes',


            'orders.*.vehicle_name_id' => 'required|integer|exists:vehicle_names,id',
            'orders.*.start_date' => 'required|date|date_format:Y-m-d', 
            'orders.*.end_date' => 'required|date|date_format:Y-m-d', 
            
            'orders.*.start_location' => 'nullable|string',
            'orders.*.end_location' => 'nullable|string',

            'orders.*.start_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'orders.*.start_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'orders.*.end_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'orders.*.end_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            'orders.*.order_description' => 'sometimes|nullable|string',

            'orders.*.with_driver' => 'required|boolean',
            'orders.*.with_fuel' => 'sometimes|nullable|boolean',
            'orders.*.periodic' => 'sometimes|nullable|boolean',

        ];
    }
}
