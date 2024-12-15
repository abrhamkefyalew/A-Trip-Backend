<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\InvoiceTrip;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PayTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('pay', InvoiceTrip::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => [
                'required', 'string', Rule::in([InvoiceTrip::INVOICE_TELE_BIRR, InvoiceTrip::INVOICE_CBE_MOBILE_BANKING, InvoiceTrip::INVOICE_CBE_BIRR, InvoiceTrip::INVOICE_BOA]),
            ],

        ];
    }
}
