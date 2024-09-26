<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\OrderUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrderUserRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrderUserRequest;


class OrderUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $this->authorize('viewAny', OrderUser::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderUserRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderUser $orderUser)
    {
        // $this->authorize('view', $orderUser);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderUserRequest $request, OrderUser $orderUser)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderUser $orderUser)
    {
        //
    }
}
