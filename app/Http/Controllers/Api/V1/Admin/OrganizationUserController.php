<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        // use Filtering service OR Scope to do this
        if ($request->has('phone_number_search')) {
            if (isset($request['phone_number_search'])) {
                $phoneNumber = $request['phone_number_search'];

                $organizationUsers = $organizationUsers->where('phone_number', $phoneNumber);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        if ($request->has('organization_id_search')) {
            if (isset($request['organization_id_search'])) {
                $organizationId = $request['organization_id_search'];

                $organizationUsers = $organizationUsers->where('organization_id', $organizationId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        if ($request->has('is_admin_search')) {
            if (isset($request['is_admin_search'])) {
                $isAdmin = $request['is_admin_search'];

                $organizationUsers = $organizationUsers->where('is_admin', $isAdmin);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }

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
                'organization_id' => $request['organization_id'],
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
        // $this->authorize('view', $organizationUser);
        return OrganizationUserResource::make($organizationUser->load('media', 'organization', 'address'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationUserRequest $request, OrganizationUser $organizationUser)
    {
        //
        $var = DB::transaction(function () use ($request, $organizationUser) {
            
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
        // $this->authorize('delete', $organizationUser);

        $var = DB::transaction(function () use ($organizationUser) {

            if (Trip::where('organization_user_id', $organizationUser->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the Organization User because it is in use by Trips.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the Organization User because it is in use by Trips.'
                ], Response::HTTP_CONFLICT);
            }

            $organizationUser->delete();

            return response()->json(true, 200);

        });

        return $var;
    }

    
    public function restore(string $id)
    {
        $organizationUser = OrganizationUser::withTrashed()->find($id);

        // $this->authorize('restore', $organizationUser);

        $var = DB::transaction(function () use ($organizationUser) {
            
            if (!$organizationUser) {
                abort(404);    
            }
    
            $organizationUser->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }

    
}
