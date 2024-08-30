<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrganizationRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrganizationRequest;
use App\Http\Resources\Api\V1\OrganizationResources\OrganizationResource;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Organization::class);

        $organizations = Organization::whereNotNull('id');

        $organizationData = $organizations->with('media')->latest()->paginate(FilteringService::getPaginate($request));

        return OrganizationResource::collection($organizationData);
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function storeOrganizationOnly(StoreOrganizationOnlyRequest $request)
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            // ORGANIZATION code Part

            $organization = Organization::create([
                'name' => $request['name'],
                'organization_description' => $request['organization_description'],
                'email' => $request['email'],
                'phone_number' => $request['phone_number'],
                'is_active' => (int) (isset($request['is_active']) ? $request['is_active'] : 1), // this works
                'is_approved' => (int) $request->input('is_approved', 0), // this works also    // // this column can ONLY be Set by the SUPER_ADMIN, // if Organization is registering himself , he can NOT send the is_approved field
                                                                                                   // so this //is_approved// code part will be removed when the Organization makes the request
            ]);


            if ($request->has('country') || $request->has('city')) {
                $organization->address()->create([
                    'country' => $request->input('country'),
                    'city' => $request->input('city'),
                ]);
            }

            // ORGANIZATION MEDIAs

            // NO organization image remove, since it is the first time the organization is being stored
            // also use the MediaService class to remove image

            if ($request->has('organization_profile_image')) {
                $file = $request->file('organization_profile_image');
                $clearMedia = false; // or true // // NO organization image remove, since it is the first time the organization is being stored
                $collectionName = Organization::ORGANIZATION_PROFILE_PICTURE;
                MediaService::storeImage($organization, $file, $clearMedia, $collectionName);
            }

            





            // ORGANIZATION USER code Part

            $organizationUser = $organization->organizationUsers()->create([
                'first_name' => $request['user_first_name'],
                'last_name' => $request['user_last_name'],
                'email' => $request['user_email'],
                'phone_number' => $request['user_phone_number'],
                'is_active' => (int) (isset($request['user_is_active']) ? $request['user_is_active'] : 1), // this works
                'is_admin' => 1,    // the organization user stored with the organization here (when the organization is created for the first time) must always be admin regardless of the user input
            ]);


            if ($request->has('user_country') || $request->has('user_city')) {
                $organizationUser->address()->create([
                    'country' => $request->input('user_country'),
                    'city' => $request->input('user_city'),
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





            return OrganizationResource::make($organization->load('media', 'address', 'contracts' /*, 'orders'*/ , 'organizationUsers'));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        // $this->authorize('view', $organization);
        return OrganizationResource::make($organization->load('media', 'address', 'contracts' /*, 'orders'*/ , 'organizationUsers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        //
    }
}
