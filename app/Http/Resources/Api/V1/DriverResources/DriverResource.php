<?php

namespace App\Http\Resources\Api\V1\DriverResources;

use App\Models\Driver;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;

class DriverResource extends JsonResource
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
            'driver_license_front_image_path' => $this->getOptimizedImagePath(Driver::DRIVER_LICENSE_FRONT_PICTURE),
            'driver_license_back_image_path' => $this->getOptimizedImagePath(Driver::DRIVER_LICENSE_BACK_PICTURE),
            'driver_id_front_image_path' => $this->getOptimizedImagePath(Driver::DRIVER_ID_FRONT_PICTURE),
            'driver_id_back_image_path' => $this->getOptimizedImagePath(Driver::DRIVER_ID_BACK_PICTURE),
            'driver_profile_image_path' => $this->getOptimizedImagePath(Driver::DRIVER_PROFILE_PICTURE),
            'address' => AddressResource::make($this->whenLoaded('address')),

            // ONE to ONE
            'driver_vehicle' => VehicleResource::make($this->whenLoaded('vehicle', function () {
                return $this->vehicle->load('supplier', 'vehicleName', 'address', 'media');
            })),
        ];
    }
}
