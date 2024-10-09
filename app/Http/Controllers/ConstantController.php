<?php

namespace App\Http\Controllers;

use App\Models\Constant;
use App\Http\Requests\StoreConstantRequest;
use App\Http\Requests\UpdateConstantRequest;

class ConstantController extends Controller
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
    public function store(StoreConstantRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Constant $constant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConstantRequest $request, Constant $constant)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Constant $constant)
    {
        //
    }
}
