<?php

namespace App\Http\Resources\Api\V1\SupplierResources;

use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;

class SupplierResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'supplier_id_front_image_path' => $this->getOptimizedImagePath(Supplier::SUPPLIER_ID_FRONT_PICTURE),
            'supplier_id_back_image_path' => $this->getOptimizedImagePath(Supplier::SUPPLIER_ID_BACK_PICTURE),
            'supplier_profile_image_path' => $this->getOptimizedImagePath(Supplier::SUPPLIER_PROFILE_PICTURE),
            'address' => AddressResource::make($this->whenLoaded('address')),
            'supplier_vehicles' => VehicleResource::collection($this->whenLoaded('vehicles', function () {
                return $this->vehicles->load('driver', 'vehicleName', 'address', 'media');
            })),
        ];
    }
}
