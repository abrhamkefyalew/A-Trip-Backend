<?php

namespace App\Http\Controllers\Api\V1\Callback\BOA;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CallbackRequests\BOA\BOACallbackRequest;

class BOACallbackController extends Controller
{
    /**
     * payment callback for invoice (comes from banks)
     * 
     * from customer or organization to adiamat
     *      //
     *      OPR = (organization PR) payment
     *      //
     *      ICI = (individual customer initial) payment
     *      ICF = (individual customer final) payment
     * 
     * from adiamat to others payment
     *      // NOTE : - 
     *      //
     *      VOO = (vehicle of Order) payment
     *      //
     *      DTF = (Driver Trip Fuel) payment
     * 
     * 
     * 
     */
    public function payInvoicesCallback(BOACallbackRequest $request)
    {
        //
        //
        $var = DB::transaction(function () use ($request) {
            if (substr($request['invoice_reference'], 0, 3) === 'OPR') {
                // go to the organization payment callback handler
            }
        });
        

        return $var;
    }




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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
