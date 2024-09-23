<?php

namespace App\Http\Controllers\Api\V1\Auth\AdminAuth;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\Api\V1\AdminResources\AdminResource;
use App\Http\Requests\Api\V1\AuthRequests\LoginAdminRequest;

class AdminAuthController extends Controller
{
    //
    
    public function login(LoginAdminRequest $request)
    {
        // better use load than with, since here after all we get the data , we are checking if the password does match, 
        // if password does not match all the data and relation and Eager Load is wasted and the data will NOT be returned
        // do first get only the admin and if the password matches then get the other relations using load()
        $admin = Admin::with(['permissions', 'address', 'roles', 'media'])->where('email', $request->email)->where('is_active', 1)->first();  // only if is_active = 1 he can login

        // request()->request->add(['admin-permission-groups' => true]);

        if ($admin) {
            if (Hash::check($request->password, $admin->password)) {

                $tokenResult = $admin->createToken('Personal Access Token', ['access-admin']);
                $expiresAt = now()->addMinutes(9886); // Set the expiration time to 240 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)  // FOR PRODUCTION MAKE THE EXPIRE DATE TO 20 MINs
                $token = $tokenResult->accessToken;
                $token->expires_at = $expiresAt;
                $token->save();
                
                //$admin->sendEmailVerificationNotification();

                return response()->json(
                    [
                        'access_token' => $tokenResult->plainTextToken,
                        'token_abilities' => $tokenResult->accessToken->abilities,
                        'token_type' => 'Bearer',
                        'expires_at' => $tokenResult->accessToken->expires_at,
                        'data' => new AdminResource($admin),
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
        
        // ok this is the admin->id but we need the token->id from the token table - will not work 
        // so do not use this, since the tokenable_id in persona_access_tokens could be repeated for different type of user MODELs // this is the token id that should be deleted as used above // $request->user()->currentAccessToken()->id
        // $user = auth()->user(); // it is the SANCTUM MIDDLE-WARE
        // $admin = Admin::find($user->id);
        // $request->user()->tokens()->where('id', $admin->id)->delete();  // NOT works


        // $request->user()->tokens()->delete(); // Will Delete all the current user Tokens and log him out of all the devices

        // WE CAN RETURN
        // 
        // return $request->user()->tokens()->get(); // gets all of this user token information from all other device sessions ALL of his active sessions (all this user tokens fromm the table) // Works but does not return the actual personal access TOKEN
        //
        // return $request->user();
        // return request()->user();
        //
        // return $admin;
        //
        // return auth()->user();
        // return auth()->user()->id;
        // return auth()->user()->email;
        //
        // return $request->user()->currentAccessToken(); // gets all the token information BUT the only current token (this session) // Works but does not return the actual personal access TOKEN
        // return $request->user()->currentAccessToken()->id // only the current token id;


        //////// Get the user's access token,  Example 1 - DOES NOT WORK ////////////////////////////////////////////////////////
        // $token = $admin->tokens()->where('name', 'Personal Access Token')->first();

        // if ($token) {
        //     // Return the access token in a JSON response
        //     return response()->json(['access_token' => $token->accessToken]);
        // } else {
        //     // Token not found
        //     return response()->json(['error' => 'Access token not found'], 404);
        // }
        //////// end Get the user's access token Example 1 - DOES NOT WORK ////////////////////////////////////////////////////////
        

        // return $request->user()->currentAccessToken()->accessToken; // gets the access token  // Example 2 - DOES NOT WORK


        // return $request->user()->currentAccessToken(); // gets all the token information // Works but does not return the actual personal access TOKEN

        // return $request->user()->currentAccessToken()->id;

        

    }

    /**
     * LogOut from All devices or Every other sessions
     */
    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout from all devices successful'], 200);
    }


    // delete this method or delete this function // abrham comment
    // public function testingUserLogin(Request $request)
    // {
    //     if ($request)
    //     {
    //         $phone = $request->phone_number;
    //     }

    //     $newJwtToken = '3|xhkGW3UMA4Hcw3Anf0Ca1uoTPi1EFEiv0i0VUxlG';

    //     return response()->json([
    //         'new_system_token' => $newJwtToken,
    //         'user_phone_number___' => $phone
    //     ]);
    // }
}
