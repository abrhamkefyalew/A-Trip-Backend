<?php

namespace App\Http\Requests\Api\V1\SupplierRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceVehicleOrderUserRequest extends FormRequest
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
            'order_user_id' => 'required|integer|exists:order_users,id',
            
            'end_date' => 'required|date|date_format:Y-m-d',
        ];
    }
}
