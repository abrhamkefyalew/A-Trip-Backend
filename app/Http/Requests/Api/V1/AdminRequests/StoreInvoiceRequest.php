<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // multiple invoices can be sent at once 
            // i will put similar invoice_code in InvoiceController = for those multiple invoices that are sent at once
            'invoices' => 'required|array',

            'invoices.*' => 'sometimes',


            'invoices.*.order_id' => 'required|integer|exists:orders,id',
            
            'invoices.*.end_date' => 'required|date|date_format:Y-m-d',



            // i should get the organization_id from the order_id
            // 'organization_id' => 'required|integer|exists:organizations,id',
            // this should be automatically calculated in the controller
            // 'price_amount' => 'required|integer|between:0,9999999',


        ];
    }
}
