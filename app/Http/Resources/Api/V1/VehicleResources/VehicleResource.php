<?php

namespace App\Http\Resources\Api\V1\VehicleResources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\BankResources\BankResource;
use App\Http\Resources\Api\V1\DriverResources\DriverResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;
use App\Http\Resources\Api\V1\SupplierResources\SupplierResource;
use App\Http\Resources\Api\V1\VehicleNameResources\VehicleNameResource;

class VehicleResource extends JsonResource
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
            'vehicle_name_id' => $this->vehicle_name_id,
            'supplier_id' => $this->supplier_id,
            'driver_id' => $this->driver_id,
            'vehicle_name' => $this->vehicle_name,
            'vehicle_description' => $this->vehicle_description,
            'vehicle_model' => $this->vehicle_model,
            'plate_number' => $this->plate_number,
            'year' => $this->year,
            'is_available' => $this->is_available,
            'with_driver' => $this->with_driver,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'bank_id' => $this->bank_id,
            'bank_account' => $this->bank_account,

            'vehicle_libre_image_path' => $this->getOptimizedImagePath(Vehicle::VEHICLE_LIBRE_PICTURE),
            'vehicle_third_person_image_path' => $this->getOptimizedImagePath(Vehicle::VEHICLE_THIRD_PERSON_PICTURE),
            'vehicle_power_of_attorney_image_path' => $this->getOptimizedImagePath(Vehicle::VEHICLE_POWER_OF_ATTORNEY_PICTURE),
            'vehicle_profile_image_path' => $this->getOptimizedImagePath(Vehicle::VEHICLE_PROFILE_PICTURE),
            
            'address' => AddressResource::make($this->whenLoaded('address')),
            'vehicle_supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'vehicle_vehicleName' => VehicleNameResource::make($this->whenLoaded('vehicleName', function () {
                return $this->vehicleName->load('vehicleType');
            })),

            // ONE to ONE
            'vehicle_driver' => DriverResource::make($this->whenLoaded('driver', function () {
                return $this->driver->load('address', 'media');
            })),

            'vehicle_bank_detail' => BankResource::make($this->whenLoaded('bank')),
        ];
    }
}
