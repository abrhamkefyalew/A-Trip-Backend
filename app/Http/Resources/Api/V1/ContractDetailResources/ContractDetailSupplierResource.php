<?php

namespace App\Http\Resources\Api\V1\ContractDetailResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractDetailSupplierResource extends JsonResource
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
            'with_driver' => $this->with_driver,
            'with_fuel' => $this->with_fuel,
            'periodic' => $this->periodic,
            'price_vehicle_payment' => $this->price_vehicle_payment,

        ];
    }
}
