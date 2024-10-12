<?php

namespace App\Http\Requests\Api\V1\CustomerRequests;

use Illuminate\Foundation\Http\FormRequest;

class PayInvoiceCallBackTelebirrRequest extends FormRequest
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
            'invoice_user_id' => [
                'required', 
                'integer', 
                // 'exists:invoice_users,id'  // COMMENTED because: - we have prefix on the invoice_user_id, (lke    "o1"-for organization  or   "i1"-for individual customer )  
                                              // the prefix will not let us check the existence of the id in the database
                                              // so we have to do existence check manually in the controller
            ],
            


        ];
    }
}
