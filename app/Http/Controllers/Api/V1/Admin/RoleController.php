<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Role;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\PermissionRole;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\Admin\RoleService;
use App\Http\Resources\Api\V1\RoleResources\RoleResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreRoleRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateRoleRequest;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        return RoleService::index($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            // $role = Role::create($request->all()); // WRONG , // should NOT use request All

            $role = Role::create($request->validated());
            //
            if (!$role) {
                return response()->json(['message' => 'Role Create Failed'], 500);
            }


            if ($request->has('permission_ids')) {
                $successTwo = $role->permissions()->attach($request->input('permission_ids'));
                //
                // this will not work B/C if the above operation is SUCCESSFUL it returns NULL to the $success variable, which is falsy in PHP
                // The attach method in Laravel returns null if the operation is successful. Therefore, checking if (!$success) might not work as expected since null is considered falsy in PHP.
                // if (!$successTwo) {
                //     return response()->json(['message' => 'Permission Attach Failed'], 500);
                // }
            } 



            // return RoleResource::make($role->load(['permissions']))->response()->setStatusCode(201);

            return RoleResource::make($role->load(['admins', 'permissions']))
                        ->response()->setStatusCode(Response::HTTP_CREATED);

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        // return new RoleResource($role->load('admins', 'permissions')->loadCount('admins'), true); // NOT sure if it works Check abrham



        // return RoleResource::make($role->load(['admins', 'permissions'])->loadCount('admins'), true); // with the boolean // check

        return RoleResource::make($role->load(['admins', 'permissions'])->loadCount('admins')); // without the boolean // check



        /*
            // The "true" Parameter: 
                // NOT SURE if the following explanation is correct but CHECK the booleans Exact meaning
                    //
                    // In Laravel, the true parameter passed at the end of new RoleResource(...) or RoleResource::make(...) corresponds to the $preserveKeys parameter. 
                    // If true, the keys of the collection or array will be preserved. If false or omitted, they will be re-indexed numerically.
     
        */    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        //
        $var = DB::transaction(function () use ($request, $role) {
            
            // commented because we are ALSO Updating PERMISSIONs of the ROLE here
            // if ($role->system_created) {
            //     return response()->json(['message' => 'System-created roles can\'t be updated.'], 400);
            // }

            $success = $role->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Role Update Failed'], 500);
            }



            if ($request->has('permission_ids')) {
                $successTwo = $role->permissions()->sync($request->input('permission_ids'));
                //
                // this will not work B/C if the above operation is SUCCESSFUL it returns NULL to the $success variable, which is falsy in PHP
                // The attach method in Laravel returns null if the operation is successful. Therefore, checking if (!$success) might not work as expected since null is considered falsy in PHP.
                // if (!$successTwo) {
                //     return response()->json(['message' => 'Permission Sync Failed'], 500);
                // }
            } 



            return RoleResource::make($role->load(['admins', 'permissions']))
                        ->response()->setStatusCode(200);

        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        $var = DB::transaction(function () use ($role) {

            if ($role->is_system_created == 1) {
                return response()->json(['message' => 'System-created roles can\'t be deleted.'], 400);
            }




            // // will NOT work as expected, but you can check
            // if ($role->admins->where('role_id', $role->id)->exists()) {
            //     return response()->json([
            //         'message' => 'Cannot delete the Role because it is in use by Admins.'
            //     ], Response::HTTP_CONFLICT);
            // }
            // // will NOT work as expected, but you can check
            // if ($role->permissions->where('role_id', $role->id)->exists()) {
            //     return response()->json([
            //         'message' => 'Cannot delete the Role because it is in use by Admins.'
            //     ], Response::HTTP_CONFLICT);
            // }



            // will WORK // definitely works
            if (AdminRole::where('role_id', $role->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the Role because it is in use by Admins.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the Role because it is in use by Admins.'
                ], Response::HTTP_CONFLICT);
            }

            // will WORK // definitely works
            if (PermissionRole::where('role_id', $role->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the Role because it is in use by Permissions.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the Role because it is in use by Permissions.'
                ], Response::HTTP_CONFLICT);
            }
    
            $role->delete();
    
            return response(null, Response::HTTP_NO_CONTENT);
        });

        return $var;

    }


    public function restore(string $id)
    {
        $role = Role::withTrashed()->find($id);

        $this->authorize('restore', $role);

        $var = DB::transaction(function () use ($role) {
            
            if (!$role) {
                abort(404);    
            }
    
            $role->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }

}
