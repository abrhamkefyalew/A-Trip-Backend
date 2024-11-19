<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\InvoiceVehicle;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PayInvoiceVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('pay', InvoiceVehicle::class);
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
                'required', 'string', Rule::in([InvoiceVehicle::INVOICE_TELE_BIRR, InvoiceVehicle::INVOICE_CBE_MOBILE_BANKING, InvoiceVehicle::INVOICE_CBE_BIRR, InvoiceVehicle::INVOICE_BOA]),
            ],

            'invoice_vehicle_id' => 'required|integer|exists:invoice_vehicles,id',
        ];
    }
}
