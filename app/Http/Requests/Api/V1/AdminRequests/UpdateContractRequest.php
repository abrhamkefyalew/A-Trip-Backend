<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
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
            'organization_id' => 'sometimes|integer|exists:organizations,id',
            
            // there should be separate endpoint to update this
            // 'terminated_date' => 'required|date|date_format:Y-m-d',

            'contract_name' => [
                'sometimes', 'string',
            ],
            'contract_description' => [
                'sometimes', 'nullable', 'string',
            ],




            // MEDIA ADD
            'organization_contract_file' => [
                'sometimes',
                'file', // Change 'image' to 'file' for all file types
                'mimes:pdf', // Allow only PDF files
                'max:49072', // Maximum file size in kilobytes (adjust as needed)
            ],


            // MEDIA REMOVE
            
            // GOOD IDEA = ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection
            

            // BAD IDEA = when doing remove image try to do it for specific collection
            'organization_contract_file_remove' => [
                'sometimes', 'boolean',
            ],
            
        ];
    }
}
