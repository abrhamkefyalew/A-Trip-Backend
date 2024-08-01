<?php

namespace App\Http\Resources\Api\V1\OrganizationResources;

use App\Models\Organization;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;
use App\Http\Resources\Api\V1\ContractResources\ContractResource;
use App\Http\Resources\Api\V1\OrganizationUserResources\OrganizationUserResource;

class OrganizationResource extends JsonResource
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
            'name' => $this->name,
            'organization_description' => $this->organization_description,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization_profile_image_path' => $this->getOptimizedImagePath(Organization::ORGANIZATION_PROFILE_PICTURE),
            'address' => AddressResource::make($this->whenLoaded('address')),

            'organization_contracts' => ContractResource::collection($this->whenLoaded('contracts', function () {
                return $this->contracts->load('contractDetails', 'media');
            })),

            'organization_organizationUsers' => OrganizationUserResource::collection($this->whenLoaded('organizationUsers', function () {
                return $this->organizationUsers->load('address', 'media');
            })),
        ];
    }
}
