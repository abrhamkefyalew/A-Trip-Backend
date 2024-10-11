<?php

namespace App\Http\Controllers\Api\V1\Auth\CustomerAuth;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Services\Api\V1\MediaService;
use App\Http\Requests\Api\V1\AuthRequests\LoginCustomerRequest;
use App\Http\Requests\Api\V1\AuthRequests\RegisterCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResources\CustomerForCustomerResource;

class CustomerAuthController extends Controller
{

    public function register(RegisterCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());
        // is_active and is_approved values will be the default values = "1"    -   -   -   - (as it was SET in the customers table migration)


        if ($request->has('country') || $request->has('city')) {
            $customer->address()->create([
                'country' => $request->input('country'),
                'city' => $request->input('city'),
            ]);
        }

        if ($request->has('customer_profile_image')) {
            $file = $request->file('customer_profile_image');
            $clearMedia = false; // or true // // NO Customer image remove, since it is the first time the driver is being Registered
            $collectionName = Customer::CUSTOMER_PROFILE_PICTURE;
            MediaService::storeImage($customer, $file, $clearMedia, $collectionName);
        }

        $tokenResult = $customer->createToken('Personal Access Token', ['access-customer']);
        //$customer->sendEmailVerificationNotification();

        return response()->json(
            [
                'access_token' => $tokenResult->plainTextToken,
                'token_abilities' => $tokenResult->accessToken->abilities,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->accessToken->expires_at,
                'data' => new CustomerForCustomerResource($customer->load(['media', 'address'])),
            ],
            201
        );
    }

    


    public function login(LoginCustomerRequest $request)
    {
        // better use load than with, since here after all we get the data , we are checking if the password does match, 
        // if password does not match all the data and relation and Eager Load is wasted and the data will NOT be returned
        // do first get only the customer and if the password matches then get the other relations using load()
        $customer = Customer::with(['address', 'media'])->where('email', $request->email)->where('is_approved', 1)->first();  // only if is_active = 1 he can login

        // request()->request->add(['customer-permission-groups' => true]);

        if ($customer) {
            if (Hash::check($request->password, $customer->password)) {

                $tokenResult = $customer->createToken('Personal Access Token', ['access-customer']);
                $expiresAt = now()->addMinutes(9886); // Set the expiration time to 240 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)  // FOR PRODUCTION MAKE THE EXPIRE DATE TO 20 MINs
                $token = $tokenResult->accessToken;
                $token->expires_at = $expiresAt;
                $token->save();
                
                //$customer->sendEmailVerificationNotification();

                return response()->json(
                    [
                        'access_token' => $tokenResult->plainTextToken,
                        'token_abilities' => $tokenResult->accessToken->abilities,
                        'token_type' => 'Bearer',
                        'expires_at' => $tokenResult->accessToken->expires_at,
                        'data' => new CustomerForCustomerResource($customer),
                    ],
                    200
                );
            }
        }

        return response()->json(['message' => 'Login failed. Incorrect email or password.'], 400);
    }


    /**
     * LogOut from one device or current session only
     */
    public function logout(Request $request)
    {
        
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
    
        return response()->json(['message' => 'Logout successful'], 200);        

    }

    /**
     * LogOut from All devices or Every other sessions
     */
    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout from all devices successful'], 200);
    }
    

}
