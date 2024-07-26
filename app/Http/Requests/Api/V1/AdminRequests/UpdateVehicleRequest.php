<?php

namespace App\Http\Requests\Api\V1\AdminRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            // TODO
            // to update the driver_id for vehicle, // since the driver_id is unique in vehicles table, 
            //         1. the Supplier should detach the driver from his previously owned vehicle, making the driver_id=NULL in vehicles table, 
            //                     for detach we should make a separate api // under supplier routes
            //         2. once the Supplier detached the driver from his previous vehicle using the separate api for detach,  
            //         3. then the Supplier can send an ATTACH request with the driver_id and vehicle_id
            //                     for attach we should make a separate api
            // 
            // NOTE (IMPORTANT):
            //         DETACH 
            //                 - when the Supplier sends detach request to detach a driver from a vehicle,   
            //                             the vehicle that the driver is Already paired with,    MUST be owned by the Supplier_id who is sending the detach request,  otherwise he is invading other suppliers data
            //         ATTACH
            //                 - when the Supplier sends attach request to attach a driver with a vehicle,
            //                             the vehicle that the driver is going to be paired with,  MUST be owned by the Supplier_id who is sending the attach request,  otherwise he is invading other suppliers data
            //     so the DETACH and ATTACH can only be done within the Supplier_id who is sending this two requests




            
        ];
    }
}
