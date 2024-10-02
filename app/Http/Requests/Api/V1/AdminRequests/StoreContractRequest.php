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
            
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d',

            // this should not be here since we are creating a contract, // and when a contract is created there shall not be the contract termination date. since it is not terminated yet
            // 'terminated_date' => 'required|date|date_format:Y-m-d',

            'contract_name' => [
                'required', 'string',
            ],
            'contract_description' => [
                'sometimes', 'nullable', 'string',
            ],




            'organization_contract_file' => [
                'required',
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
