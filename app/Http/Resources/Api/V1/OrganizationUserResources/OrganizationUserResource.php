<?php

namespace App\Http\Resources\Api\V1\OrganizationUserResources;

use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use App\Models\OrganizationUser;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;
use App\Http\Resources\Api\V1\OrganizationResources\OrganizationResource;

class OrganizationUserResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'organization_user_profile_image_path' => $this->getOptimizedImagePath(OrganizationUser::ORGANIZATION_USER_PROFILE_PICTURE),
            
            'address' => AddressResource::make($this->whenLoaded('address')),

            'organization' => OrganizationResource::make($this->whenLoaded('organization', function () {
                return $this->organization->load('contracts', 'address', 'media');
            })),
        ];
    }
}
