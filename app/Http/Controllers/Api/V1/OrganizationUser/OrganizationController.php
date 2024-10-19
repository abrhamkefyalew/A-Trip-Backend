<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Http\Resources\Api\V1\OrganizationResources\OrganizationResource;
use App\Http\Requests\Api\V1\OrganizationUserRequests\UpdateOrganizationRequest;

class OrganizationController extends Controller
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
    public function show(Organization $organization)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        //
        $var = DB::transaction(function () use ($request, $organization) {
            
            $user = auth()->user();
            $organizationUserLoggedIn = OrganizationUser::find($user->id);


            // check if the organizationUser is Authorized to update the passed organizationUser
            if ($organizationUserLoggedIn->is_admin != 1) {
                return response()->json(['message' => 'Unauthorized. you should be organization Admin to update organization profile'], 401);
            }

            if ($organizationUserLoggedIn->organization_id != $organization->id) {
                return response()->json(['message' => 'invalid Organization is selected or Requested. Deceptive request Aborted.'], 403);
            }



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
