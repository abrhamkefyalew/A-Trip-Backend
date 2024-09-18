<?php

namespace App\Http\Requests\Api\V1\OrganizationUserRequests;

use Illuminate\Foundation\Http\FormRequest;

class PayInvoiceRequest extends FormRequest
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
            'price_amount_total' => 'required|integer|between:0,9999999',



            'invoices' => 'required|array',

            'invoices.*' => 'sometimes',


            'invoices.*.invoice_id' => 'required|integer|exists:invoices,id',


        ];
    }
}
