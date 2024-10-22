<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Http\Requests\StoreOtpRequest;
use App\Http\Requests\UpdateOtpRequest;

class OtpController extends Controller
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
    public function store(StoreOtpRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Otp $otp)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOtpRequest $request, Otp $otp)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Otp $otp)
    {
        //
    }
}
