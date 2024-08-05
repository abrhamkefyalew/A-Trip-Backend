<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        // return $this->user()->can('create', Contract::class);
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
            'organization_id' => 'required|integer|exists:organizations,id',
            
            'start_date' => [
                'required', 'date',
            ],
            'end_date' => [
                'required', 'date',
            ],
            'is_active' => [
                'sometimes', 'nullable', 'boolean',
            ],

            'terminated_date' => [
                'sometimes', 'nullable', 'date',
            ],


            'organization_contract_file' => [
                'sometimes',
                'file', // Change 'image' to 'file' for all file types
                'mimes:pdf', // Allow only PDF files
                'max:3072', // Maximum file size in kilobytes (adjust as needed)
            ],



            // since we are just storing a new contract so no need to delete the the others older contract files //
            // a contract files shall be removed ONLY when you update that contract
            // even when you modify a contract with another contract_id but similar contract_code you must NOT remove file, 
                // because the new contract modification is done in a new contract row and needs its own media, so there is no need to remove the older contract media

            // NO contract file remove, since it is the first time the contract is being stored
            // also use the MediaService class to remove file

            // in conclusion since contract is being stored for the first time , there is no need to remove contract file
            // 'organization_contract_file_remove' => [
            //     'sometimes', 'boolean',
            // ],

        ];
    }
}
