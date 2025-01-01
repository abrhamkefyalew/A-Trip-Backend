<?php

namespace App\Http\Resources\Api\V1\CustomerResources;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;

class CustomerForCustomerResource extends JsonResource
{
    use GetMedia;
    
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'is_approved' => $this->is_approved,
            'customer_profile_image_path' => $this->getOptimizedImagePath(Customer::CUSTOMER_PROFILE_PICTURE),
            'customer_id_front_image_path' => $this->getOptimizedImagePath(Customer::CUSTOMER_ID_FRONT_PICTURE),
            'customer_id_back_image_path' => $this->getOptimizedImagePath(Customer::CUSTOMER_ID_BACK_PICTURE),
            'address' => AddressResource::make($this->whenLoaded('address')),

        ];
    }
}
