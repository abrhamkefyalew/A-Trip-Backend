<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\OrganizationUserResources\OrganizationUserResource;
use App\Http\Requests\Api\V1\OrganizationUserRequests\StoreOrganizationUserRequest;
use App\Http\Requests\Api\V1\OrganizationUserRequests\UpdateOrganizationUserRequest;

class OrganizationUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);
        
        $organization = OrganizationUser::where('organization_id', $organizationUser->organization_id);
        
        $organizationData = $organization->with('vehicleName', 'vehicle', 'driver', 'contractDetail')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of the organization

        return OrganizationUserResource::collection($organizationData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationUserRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            $user = auth()->user();
            $organizationUserLoggedIn = OrganizationUser::find($user->id);

            // check if the organizationUser is organization admin
            if ($organizationUserLoggedIn->is_admin !== 1) {
                return response()->json(['message' => 'UnAuthorized. you are not organization Admin'], 401); 
            }
            
            
            $organizationUser = OrganizationUser::create([
                'organization_id' => $organizationUserLoggedIn->organization_id,
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'email' => $request['email'],
                'password' => $request['password'],
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationUserRequest $request, OrganizationUser $organizationUser)
    {
        //
        $var = DB::transaction(function () use ($request, $organizationUser) {

            $user = auth()->user();
            $organizationUserLoggedIn = OrganizationUser::find($user->id);


            // check if the organizationUser is Authorized to update the passed organizationUser
            if ($organizationUserLoggedIn->is_admin != 1) {

                if ($organizationUserLoggedIn->id != $organizationUser->id) {
                
                    return response()->json(['message' => 'invalid Organization User is selected or Requested. Deceptive request Aborted.'], 403);
                }

            } else if ($organizationUserLoggedIn->is_admin == 1) {

                if ($organizationUserLoggedIn->organization_id != $organizationUser->organization_id) {
                
                    return response()->json(['message' => 'invalid Organization User is selected or Requested by Organization Admin. Deceptive request Aborted.'], 403);
                }

            } else {
                return response()->json(['message' => 'Invalid Organization Role.'], 404); 
            }
            
            

            
            $success = $organizationUser->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }
            

            if ($request->has('country') || $request->has('city')) {
                if ($organizationUser->address) {
                    $organizationUser->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $organizationUser->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }



            // MEDIA CODE SECTION
            // REMEMBER = (clearMedia) ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection // check abrham samson // remember
            //
            if ($request->has('organization_user_profile_image')) {
                $file = $request->file('organization_user_profile_image');
                $clearMedia = $request->input('organization_user_profile_image_remove', false);
                $collectionName = OrganizationUser::ORGANIZATION_USER_PROFILE_PICTURE;
                MediaService::storeImage($organizationUser, $file, $clearMedia, $collectionName);
            }

            
            $updatedOrganizationUser = OrganizationUser::find($organizationUser->id);

            return OrganizationUserResource::make($updatedOrganizationUser->load('media', 'organization', 'address'));

        });

        return $var;

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganizationUser $organizationUser)
    {
        //
    }
}
