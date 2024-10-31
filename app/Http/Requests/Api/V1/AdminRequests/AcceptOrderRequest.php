<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class AcceptOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // do AUTH here or in the controller
        return $this->user()->can('update', Order::class);
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
            'vehicle_id' => 'required|integer|exists:vehicles,id',
        ];
    }
}
