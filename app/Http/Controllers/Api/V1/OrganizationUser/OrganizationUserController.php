<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Http\Resources\Api\V1\OrganizationUserResources\OrganizationUserResource;
use App\Http\Requests\Api\V1\OrganizationUserRequests\UpdateOrganizationUserRequest;

class OrganizationUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
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
                
                    return response()->json(['message' => 'invalid Organization User is selected or Requested. Deceptive request Aborted.'], 401);
                }

            } else if ($organizationUserLoggedIn->is_admin == 1) {

                if ($organizationUserLoggedIn->organization_id != $organizationUser->organization_id) {
                
                    return response()->json(['message' => 'invalid Organization User is selected or Requested by Organization Admin. Deceptive request Aborted.'], 401);
                }

            } else {
                return response()->json(['message' => 'Invalid Organization Role.'], 401); 
            }
            
            

            
            $success = $organizationUser->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
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
