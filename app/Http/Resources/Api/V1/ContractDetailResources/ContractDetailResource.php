<?php

namespace App\Http\Resources\Api\V1\ContractDetailResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\ContractResources\ContractResource;
use App\Http\Resources\Api\V1\VehicleNameResources\VehicleNameResource;

class ContractDetailResource extends JsonResource
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
            'contract_id' => $this->contract_id,
            'vehicle_name_id' => $this->vehicle_name_id,
            'with_driver' => $this->with_driver,
            'with_fuel' => $this->with_fuel,
            'periodic' => $this->periodic,
            'price_contract' => $this->price_contract,
            'price_vehicle_payment' => $this->price_vehicle_payment,
            'tax' => $this->tax,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'contract' => ContractResource::make($this->whenLoaded('contract', function () {
                return $this->contract->load('organization', 'media');
            })),

            'vehicle_name' => VehicleNameResource::make($this->whenLoaded('vehicleName', function () {
                return $this->vehicleName->load('vehicleType', 'vehicles');
            })),

        ];
    }
}
