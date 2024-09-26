<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Bid;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreBidRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateBidRequest;


class BidController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $this->authorize('viewAny', Bid::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBidRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Bid $bid)
    {
        // $this->authorize('view', $bid);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBidRequest $request, Bid $bid)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bid $bid)
    {
        //
    }
}
