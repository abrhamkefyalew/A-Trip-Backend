<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\OrderResources\OrderResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrderRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrderRequest;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Order::class);

        // use Filtering service OR Scope to do this
        if ($request->has('organization_id_search')) {
            if (isset($request['organization_id_search'])) {
                $organizationId = $request['organization_id_search'];

                $orders = Order::where('organization_id', $organizationId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        else {
            $orders = Order::whereNotNull('id');
        }

        $ordersData = $orders->with('organization', 'vehicleName', 'vehicle', 'supplier', 'driver', 'contractDetail')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of the organization

        return OrderResource::collection($ordersData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
