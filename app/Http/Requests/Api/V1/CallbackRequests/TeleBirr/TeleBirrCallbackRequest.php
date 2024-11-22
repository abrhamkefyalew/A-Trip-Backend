<?php

namespace App\Http\Requests\Api\V1\CallbackRequests\TeleBirr;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class TeleBirrCallbackRequest extends FormRequest
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
            
            'merch_order_id' => [            // this should be the 'variable key name' tha banks send in the body
                'required',
                'string',
                // 'integer',                 // COMMENTED because THIS id is NOT Integer,  because it have prefix (like    "o84"-for organization  or   "i84"-for individual customer)      or      they may also send the id as string even if it does not have prefix
                // 'exists:invoice_users,id'  // COMMENTED because: - we have prefix on the invoice_user_id, (like    "o84"-for organization  or   "i84"-for individual customer) 
                                                                      // the prefix will not let us check the existence of the id in the database
                                                                      // so we have to do existence check manually in the controller
            ],
        ];
    }

    
    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        // Simple Way
        // Log and alert
        Log::alert('TeleBirr: invoice_reference must be included in the request!');
        abort(404, 'the invoice_code does not exist!');



        /*
        // Advanced way                     // NOT TESTED
        $errors = $validator->errors();

        $failedRules = $validator->failed();

        if (array_key_exists('invoice_reference', $failedRules)) {
            foreach ($failedRules['invoice_reference'] as $rule => $ruleData) {
                switch ($rule) {
                    case 'required':
                        Log::alert('TeleBirr: invoice_reference is required!');
                        break;
                    case 'string':
                        Log::alert('TeleBirr: invoice_reference must be a string!');
                        break;
                    // Add more cases for other rules if needed
                }
            }
        }
        //
        // Optionally, I can customize the response for each type of error
        if ($errors->has('invoice_reference')) {
            abort(400, 'Validation failed for invoice_reference is required and must be string.');
        }

        */
       
    }

}
