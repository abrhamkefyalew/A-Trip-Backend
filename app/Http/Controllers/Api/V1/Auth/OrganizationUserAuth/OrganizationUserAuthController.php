<?php

namespace App\Http\Controllers\Api\V1\Auth\OrganizationUserAuth;

use Illuminate\Http\Request;
use App\Models\OrganizationUser;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\V1\AuthRequests\LoginOrganizationUserRequest;
use App\Http\Resources\Api\V1\OrganizationUserResources\OrganizationUserResource;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Auth;


class OrganizationUserAuthController extends Controller
{
    public function login(LoginOrganizationUserRequest $request)
    {
        // better use load than with, since here after all we get the data , we are checking if the password does match, 
        // if password does not match all the data and relation and Eager Load is wasted and the data will NOT be returned
        // do first get only the organizationUser and if the password matches then get the other relations using load()
        $organizationUser = OrganizationUser::with(['address', 'organization', 'media'])->where('email', $request->email)->where('is_active', 1)->first(); 

        if ($organizationUser) {
            // if (Hash::check($request->password, $organizationUser->password)) {
                $tokenResult = $organizationUser->createToken('Personal Access Token', ['access-organizationUser']);
                $expiresAt = now()->addMinutes(950); // Set the expiration time to 50 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)
                $token = $tokenResult->accessToken;
                $token->expires_at = $expiresAt;
                $token->save();
                
                //$organizationUser->sendEmailVerificationNotification();

                return response()->json(
                    [
                        'access_token' => $tokenResult->plainTextToken,
                        'token_abilities' => $tokenResult->accessToken->abilities,
                        'token_type' => 'Bearer',
                        'expires_at' => $tokenResult->accessToken->expires_at,
                        'data' => new OrganizationUserResource($organizationUser),
                    ],
                    200
                );
            // }
        }

        return response()->json(['message' => 'Login failed. Incorrect email or password.'], 400);
    }


   

    public function loginWithFirebase(LoginOrganizationUserRequest $request)
    {
       // Launch Firebase Auth
       $auth = app('firebase.auth');
       // Retrieve the Firebase credential's token
       $idTokenString = $request->input('firebase_token');

       $newlyRegistered = false;

       try { // Try to verify the Firebase credential token with Google

           $verifiedIdToken = $auth->verifyIdToken($idTokenString);
       } catch (\InvalidArgumentException $e) { // If the token has the wrong format

           return response()->json([
               'message' => 'Unauthorized - Can\'t parse the token: '.$e->getMessage(),
           ], 401);
       } catch (\Lcobucci\JWT\Token\InvalidTokenStructure $e) { // If the token is invalid (expired ...)

           return response()->json([
               'message' => 'Unauthorized - Token is invalid: '.$e->getMessage(),
           ], 401);
       }

       // Retrieve the UID (User ID) from the verified Firebase credential's token
       $uid = $verifiedIdToken->claims()->get('sub');

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
