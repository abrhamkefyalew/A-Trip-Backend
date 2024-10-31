<?php

namespace App\Http\Resources\Api\V1\AdminResources;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Traits\Api\V1\GetMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\RoleResources\RoleResource;
use App\Http\Resources\Api\V1\AddressResources\AddressResource;
use App\Http\Resources\Api\V1\PermissionResources\PermissionResource;

class AdminResource extends JsonResource
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
            'admin_profile_image_path' => $this->getOptimizedImagePath(Admin::ADMIN_PROFILE_PICTURE),
            'address' => AddressResource::make($this->whenLoaded('address')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),

        ];
    }
}
