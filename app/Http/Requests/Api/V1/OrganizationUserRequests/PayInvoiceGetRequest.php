<?php

namespace App\Http\Requests\Api\V1\OrganizationUserRequests;

use App\Models\Invoice;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PayInvoiceGetRequest extends FormRequest
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
            'organization_user_id' => 'required|integer|exists:organization_users,id',

            'price_amount_total' => 'required|integer|between:1,9999999',

            'payment_method' => [
                'required', 'string', Rule::in([Invoice::INVOICE_TELE_BIRR, Invoice::INVOICE_CBE_MOBILE_BANKING, Invoice::INVOICE_CBE_BIRR, Invoice::INVOICE_BOA]),
            ],



            'invoices' => 'required|array',

            'invoices.*' => 'sometimes',


            'invoices.*.invoice_id' => 'required|integer|exists:invoices,id',


        ];
    }
}
