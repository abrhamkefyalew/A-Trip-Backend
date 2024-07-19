<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\OrganizationUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrganizationUserRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrganizationUserRequest;

class OrganizationUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationUserRequest $request)
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
