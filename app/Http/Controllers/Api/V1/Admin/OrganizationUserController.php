<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrganizationUserRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrganizationUserRequest;
use App\Http\Resources\Api\V1\OrganizationUserResources\OrganizationUserResource;

class OrganizationUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', OrganizationUser::class);

        $organizationUsers = OrganizationUser::whereNotNull('id');

        $organizationUserData = $organizationUsers->with('media', 'organization')->latest()->paginate(FilteringService::getPaginate($request));

        return OrganizationUserResource::collection($organizationUserData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationUserRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            $organizationUser = OrganizationUser::create([
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'email' => $request['email'],
                'phone_number' => $request['phone_number'],
                'is_active' => (int) (isset($request['is_active']) ? $request['is_active'] : 1), // this works
                'is_admin' => (int) $request->input('is_admin', 0), // this works also
            ]);


            if ($request->has('country') || $request->has('city')) {
                $organizationUser->address()->create([
                    'country' => $request->input('country'),
                    'city' => $request->input('city'),
                ]);
            }

            
            // ORGANIZATION USER MEDIAs

            // NO organization_user image remove, since it is the first time the organization_user is being stored
            // also use the MediaService class to remove image

            if ($request->has('organization_user_profile_image')) {
                $file = $request->file('organization_user_profile_image');
                $clearMedia = false; // or true // // NO organization_user image remove, since it is the first time the organization_user is being stored
                $collectionName = OrganizationUser::ORGANIZATION_USER_PROFILE_PICTURE;
                MediaService::storeImage($organizationUser, $file, $clearMedia, $collectionName);
            }


            return OrganizationUserResource::make($organizationUser->load('media', 'organization', 'address'));
            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(OrganizationUser $organizationUser)
    {
        // $this->authorize('view', $organizationUser);
        return OrganizationUserResource::make($organizationUser->load('media', 'organization', 'address'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationUserRequest $request, OrganizationUser $organizationUser)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganizationUser $organizationUser)
    {
        //
    }
}
