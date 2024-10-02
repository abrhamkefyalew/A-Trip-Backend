<?php

namespace App\Http\Requests\Api\V1\CustomerRequests;

use App\Models\InvoiceUser;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AcceptBidRequest extends FormRequest
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
            'payment_method' => [
                'required', 'string', Rule::in([InvoiceUser::INVOICE_TELE_BIRR, InvoiceUser::INVOICE_CBE_MOBILE_BANKING, InvoiceUser::INVOICE_CBE_BIRR, InvoiceUser::INVOICE_BOA]),
            ],
        ];
    }
}
