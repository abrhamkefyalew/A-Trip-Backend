<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Bank;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\BankResources\BankResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreBankRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateBankRequest;


class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $this->authorize('viewAny', Bank::class);

        // scope should be used here
        if (isset($request['paginate'])) {
            if ($request['paginate'] == "all"){
                $bank = Bank::get();
            }
            else {
                $bank = Bank::paginate(FilteringService::getPaginate($request));
            }
        } else {
            $bank = Bank::get();
        }


        return BankResource::collection($bank);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            $bank = Bank::create($request->validated());

            return BankResource::make($bank);
            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Bank $bank)
    {
        $this->authorize('view', $bank);
        
        return BankResource::make($bank->load('vehicles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankRequest $request, Bank $bank)
    {
        $var = DB::transaction(function () use($request, $bank) {
            $bank->update($request->validated());

            return BankResource::make($bank);
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bank $bank)
    {
        $this->authorize('delete', $bank);

        $var = DB::transaction(function () use ($bank) {

            if (Vehicle::where('bank_id', $bank->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the bank because it is in use by Vehicles.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the bank because it is in use by Vehicles.'
                ], Response::HTTP_CONFLICT);
            }

            $bank->delete();

            return response()->json(true, 200);

        });

        return $var;
    }

    
    public function restore(string $id)
    {
        $bank = Bank::withTrashed()->find($id);

        $this->authorize('restore', $bank);

        $var = DB::transaction(function () use ($bank) {
            
            if (!$bank) {
                abort(404);    
            }
    
            $bank->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }


}
