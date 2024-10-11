<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\Admin\RoleService;
use App\Http\Requests\Api\V1\AdminRequests\StoreRoleRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateRoleRequest;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Role::class);

        return RoleService::index($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // $this->authorize('delete', $role);

        $var = DB::transaction(function () use ($role) {
            if ($role->is_system_created == 1) {
                return response()->json(['message' => 'System-created roles can\'t be deleted.'], 400);
            }
    
            $role->delete();
    
            return response(null, Response::HTTP_NO_CONTENT);
        });

        return $var;

    }


    public function restore(string $id)
    {
        // $this->authorize('restore', $bank);

        $var = DB::transaction(function () use ($id) {

            $role = Role::withTrashed()->find($id);
            
            if (!$role) {
                abort(404);    
            }
    
            $role->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }

}
