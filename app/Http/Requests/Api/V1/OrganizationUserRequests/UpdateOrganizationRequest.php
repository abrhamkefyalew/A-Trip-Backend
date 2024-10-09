<?php

namespace App\Http\Requests\Api\V1\OrganizationUserRequests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
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
            'name' => [
                'sometimes', 'string',
            ],
            'organization_description' => [
                'sometimes', 'nullable', 'string',
            ],
            'email' => [
                'sometimes', 'email', Rule::unique('organizations')->ignore($this->organization->id),
            ],
            'phone_number' => [
                'sometimes', 'numeric', Rule::unique('organizations')->ignore($this->organization->id),
            ],
            'is_active' => [
                'sometimes', 'boolean',
            ],

            // this will only be here , in the admin request // it will be removed from the request list for Everyone Else who is making a request
            // there should be separate endpoint to update this  // if 0, all organization users should be logged out Automatically
            // 'is_approved' => [
            //     'sometimes', 'boolean',
            // ],

            'country' => [
                'sometimes', 'string',
            ],
            'city' => [
                'sometimes', 'string',
            ],


            // MEDIA ADD
            'organization_profile_image' => [
                'sometimes',
                'image',
                'max:3072',
            ],


            // MEDIA REMOVE
            
            // GOOD IDEA = ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection
            

            // BAD IDEA = when doing remove image try to do it for specific collection
            'organization_profile_image_remove' => [
                'sometimes', 'boolean',
            ],
            
        ];
    }
}
