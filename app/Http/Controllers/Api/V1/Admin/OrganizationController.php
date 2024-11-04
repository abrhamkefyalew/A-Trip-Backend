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
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('phone_number_search')) {
            if (isset($request['phone_number_search'])) {
                $phoneNumber = $request['phone_number_search'];

                $organizations = $organizations->where('phone_number', $phoneNumber);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }

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
                'is_approved' => (int) $request->input('is_approved', 1), // this works also    // // this column can ONLY be Set by the SUPER_ADMIN, // if Organization is registering himself , he can NOT send the is_approved field
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
                'password' => $request['user_password'],
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
        $this->authorize('view', $organization);
        return OrganizationResource::make($organization->load('media', 'address', 'contracts' /*, 'orders'*/ , 'organizationUsers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        //
        $var = DB::transaction(function () use ($request, $organization) {
            
            $success = $organization->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }
            

            if ($request->has('country') || $request->has('city')) {
                if ($organization->address) {
                    $organization->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $organization->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }



            // MEDIA CODE SECTION
            // REMEMBER = (clearMedia) ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection // check abrham samson // remember
            //
            if ($request->has('organization_profile_image')) {
                $file = $request->file('organization_profile_image');
                $clearMedia = $request->input('organization_profile_image_remove', false);
                $collectionName = Organization::ORGANIZATION_PROFILE_PICTURE;
                MediaService::storeImage($organization, $file, $clearMedia, $collectionName);
            }

            
            $updatedOrganization = Organization::find($organization->id);

            return OrganizationResource::make($updatedOrganization->load('media', 'address', 'contracts' /*, 'orders'*/ , 'organizationUsers'));

        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        //
    }
}
