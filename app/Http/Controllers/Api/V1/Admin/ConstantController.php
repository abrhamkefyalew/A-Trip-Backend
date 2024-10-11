<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreConstantRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateConstantRequest;
use App\Http\Resources\Api\V1\ConstantResources\ConstantResource;

class ConstantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        // $this->authorize('viewAny', Constant::class);

        // use Filtering service OR Scope to do this
        if (isset($request['paginate'])) {
            if ($request['paginate'] == "all"){
                $constant = Constant::get();
            }
            else {
                $constant = Constant::paginate(FilteringService::getPaginate($request));
            }
        } else {
            $constant = Constant::get();
        }


        return ConstantResource::collection($constant);
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
        $var = DB::transaction(function () use($request, $constant) {

            // this is not needed, if the percent_value is not sent in the request, then $request->validated() will be empty array, Laravel will not do update on any field, the table and the data in the table will remain as it was
            // if (! $request->has('percent_value')) {
            //     return response()->json(['message' => 'must send percent value.'], 404); 
            // }
            // if (! isset($request['percent_value'])) { 
            //     return response()->json(['message' => 'must set percent value.'], 404); 
            // }

            $constant->update($request->validated());

            return ConstantResource::make($constant);
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Constant $constant)
    {
        //
    }
}
