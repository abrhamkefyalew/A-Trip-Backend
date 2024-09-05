<?php

namespace App\Http\Controllers\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OrderController extends Controller
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

    // /**
    //  * Update the specified resource in storage.
    //  * 
    //  * to Accept Order = ORDER_STATUS_SET
    //  */
    // public function acceptOrder(AcceptOrderRequest $request, Order $order)
    // {
    //     //
    //     $var = DB::transaction(function () use ($request, $order) {
    //         // get the supplier identity
    //         $user = auth()->user();
    //         $supplier = Supplier::find($user->id);


    //         $vehicle = Vehicle::find($request['vehicle_id']);

    //         if ($supplier->id != $vehicle->supplier_id) {
    //             return response()->json(['message' => 'invalid Vehicle is selected. or This Supplier does not have a vehicle with this id. Deceptive request Aborted.'], 401); 
    //         }

    //         if ($order->status !== Order::ORDER_STATUS_PENDING) {
    //             return response()->json(['message' => 'invalid Vehicle is selected. or This Supplier does not have a vehicle with this id. Deceptive request Aborted.'], 401); 
    //         }
            
    //         $success = $order->update([
    //             'vehicle_id' => $request['vehicle_id'],
    //             'supplier_id' => $supplier->id,
    //             'status' => Order::ORDER_STATUS_SET,
    //         ]);

            
    //         if (!$success) {
    //             return response()->json(['message' => 'Update Failed'], 422);
    //         }

    //         $updatedOrder = Order::find($order->id);
                
    //         return OrderForSupplierResource::make($updatedOrder->load('vehicleName', 'vehicle', 'driver', 'contractDetail'));
                 
    //     });

    //     return $var;
    // }


    // /**
    //  * Update the specified resource in storage.
    //  * 
    //  * to Start Order = ORDER_STATUS_START
    //  */
    // public function startOrder(UpdateOrderRequest $request, Order $order)
    // {
        

    // }



    // /**
    //  * Update the specified resource in storage.
    //  * 
    //  * to Start Order = ORDER_STATUS_COMPLETE
    //  */
    // public function completeOrder(UpdateOrderRequest $request, Order $order)
    // {

    // }
    

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
