<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Order;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Supplier;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DashBoardAdminResources\DashBoardAdminResource;

class DashBoardController extends Controller
{

    /**
     * GET = Counts (supplier, driver, vehicles, orders, Organizations)
     */
    public function DashBoardCountOne()
    {
        //
        $counts = [
            'suppliers' => [
                'total' => Supplier::count(),
                'active' => Supplier::where('is_active', 1)->count(),
                'inactive' => Supplier::where('is_active', 0)->count(),
            ],
            'drivers' => [
                'total' => Driver::count(),
                'active' => Driver::where('is_active', 1)->count(),
                'inactive' => Driver::where('is_active', 0)->count(),
            ],
            'organizations' => [
                'total' => Organization::count(),
                'active' => Organization::where('is_active', 1)->count(),
                'inactive' => Organization::where('is_active', 0)->count(),
            ],
            'vehicles' => [
                'total' => Vehicle::count(),
                'available' => Vehicle::where('is_available', Vehicle::VEHICLE_AVAILABLE)->count(),
                'not_available' => Vehicle::where('is_available', Vehicle::VEHICLE_NOT_AVAILABLE)->count(),
                'on_trip' => Vehicle::where('is_available', Vehicle::VEHICLE_ON_TRIP)->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'pending' => Order::where('status', Order::ORDER_STATUS_PENDING)->count(),
                'set' => Order::where('status', Order::ORDER_STATUS_SET)->count(),
                'started' => Order::where('status', Order::ORDER_STATUS_START)->count(),
                'complete' => Order::where('status', Order::ORDER_STATUS_COMPLETE)->count(),
            ],
            
        ];

        return DashBoardAdminResource::make($counts);

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
