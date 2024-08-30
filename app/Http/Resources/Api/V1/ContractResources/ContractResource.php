<?php

namespace App\Http\Resources\Api\V1\ContractResources;

use App\Models\Contract;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\OrganizationResources\OrganizationResource;
use App\Http\Resources\Api\V1\ContractDetailResources\ContractDetailResource;

class ContractResource extends JsonResource
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
            'contract_code' => $this->contract_code,
            'organization_id' => $this->organization_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'terminated_date' => $this->terminated_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization_profile_image_path' => $this->getPDFPath(Contract::ORGANIZATION_CONTRACT_FILE),

            'organization' => OrganizationResource::make($this->whenLoaded('organization', function () {
                return $this->organization->load('address', 'media');
            })),

            'contract_details' => ContractDetailResource::collection($this->whenLoaded('contractDetails', function () {
                return $this->contractDetails->load('vehicleName');
            })),

        ];
    }
}
