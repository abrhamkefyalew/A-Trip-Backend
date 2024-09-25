<?php

namespace App\Http\Controllers;

use App\Models\OrderUser;
use App\Http\Requests\StoreOrderUserRequest;
use App\Http\Requests\UpdateOrderUserRequest;

class OrderUserController extends Controller
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
        //
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
