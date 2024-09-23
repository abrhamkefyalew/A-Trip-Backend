<?php

namespace App\Http\Resources\Api\V1\OrderResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\DriverResources\DriverResource;
use App\Http\Resources\Api\V1\InvoiceResources\InvoiceResource;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;
use App\Http\Resources\Api\V1\SupplierResources\SupplierResource;
use App\Http\Resources\Api\V1\VehicleNameResources\VehicleNameResource;
use App\Http\Resources\Api\V1\OrganizationResources\OrganizationResource;
use App\Http\Resources\Api\V1\ContractDetailResources\ContractDetailResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'organization_id' => $this->organization_id,
            'contract_detail_id' => $this->contract_detail_id,

            'vehicle_name_id' => $this->vehicle_name_id,
            
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'supplier_id' => $this->supplier_id,

            'start_date' => $this->start_date,
            'begin_date' => $this->begin_date,
            'end_date' => $this->end_date,

            'start_location' => $this->start_location,
            'end_location' => $this->end_location,

            'start_latitude' => $this->start_latitude,
            'start_longitude' => $this->start_longitude,
            'end_latitude' => $this->end_latitude,
            'end_longitude' => $this->end_longitude,

            'status' => $this->status,
            'is_terminated' => $this->is_terminated,

            'pr_status' => $this->pr_status,
            'order_description' => $this->order_description,
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'vehicle_name' => VehicleNameResource::make($this->whenLoaded('vehicleName', function () {
                return $this->vehicleName->load('vehicleType');
            })),

            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle', function () {
                return $this->vehicle->load('address', 'media');
            })),

            'vehicle_supplier' => SupplierResource::make($this->whenLoaded('supplier', function () {
                return $this->supplier->load('address', 'media');
            })), 


            // ONE to ONE
            // create custom DriverForOrganizationResource for organizations if you want
            // but for now this will do
            'vehicle_driver' => DriverResource::make($this->whenLoaded('driver', function () {
                return $this->driver->load('address', 'media');
            })),

            'organization' => OrganizationResource::make($this->whenLoaded('organization', function () {
                return $this->organization->load('address', 'media');
            })),

            'contract_detail' => ContractDetailResource::make($this->whenLoaded('contractDetail')),

            'order_invoices' => InvoiceResource::make($this->whenLoaded('invoices')),
            
        ];
    }
}
