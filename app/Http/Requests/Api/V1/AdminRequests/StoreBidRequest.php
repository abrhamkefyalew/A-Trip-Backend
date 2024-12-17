<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Bid;
use Illuminate\Foundation\Http\FormRequest;

class StoreBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Bid::class);
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
            'order_user_id' => 'required|integer|exists:order_users,id',
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'price_total' => 'required|integer|between:1,9999999',
        ];
    }
}
