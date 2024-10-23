<?php

namespace App\Http\Controllers\Api\V1\Auth\DriverAuth;

use Carbon\Carbon;
use App\Models\Driver;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Util\Api\V1\OtpCodeGenerator;
use App\Services\Api\V1\General\SMS\SMSService;
use App\Http\Requests\Api\V1\AuthRequests\LoginDriverRequest;
use App\Http\Resources\Api\V1\DriverResources\DriverResource;
use App\Http\Requests\Api\V1\AuthRequests\Otp\LoginOtpDriverRequest;
use App\Http\Requests\Api\V1\AuthRequests\Otp\VerifyOtpDriverRequest;

// use Kreait\Firebase\Auth;
// use Kreait\Firebase\Factory;
// use Kreait\Firebase\ServiceAccount;

class DriverAuthController extends Controller
{
    public function login(LoginDriverRequest $request)
    {
        // better use load than with, since here after all we get the data , we are checking if the password does match,   
        // if password does not match all the data and relation and Eager Load is wasted and the data will NOT be returned
        // do first get only the driver and if the password matches then get the other relations using load()
        $driver = Driver::with(['address', 'media', 'vehicle'])->where('email', $request->email)->where('is_approved', 1)->first(); 

        if ($driver) {
            if (Hash::check($request->password, $driver->password)) {
                $tokenResult = $driver->createToken('Personal Access Token', ['access-driver']);
                $expiresAt = now()->addMinutes(9950); // Set the expiration time to 50 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)
                $token = $tokenResult->accessToken;
                $token->expires_at = $expiresAt;
                $token->save();
                
                //$driver->sendEmailVerificationNotification();

                return response()->json(
                    [
                        'access_token' => $tokenResult->plainTextToken,
                        'token_abilities' => $tokenResult->accessToken->abilities,
                        'token_type' => 'Bearer',
                        'expires_at' => $tokenResult->accessToken->expires_at,
                        'data' => new DriverResource($driver),
                    ],
                    200
                );
            }
        }

        return response()->json(['message' => 'Login failed. Incorrect email or password.'], 400);
    }







    public function loginOtp(LoginOtpDriverRequest $request)
    {
        
        $driver = Driver::where('phone_number', $request['phone_number'])->first();

        if (!$driver) {
            return response()->json(['message' => 'Login failed. Account does NOT exist.'], 404);
        }

        if ($driver->is_approved != 1) {
            return response()->json(['message' => 'Login failed. Account NOT approved.'], 401);
        }


        $otpCode = OtpCodeGenerator::generate(6);


        // Generate current datetime
        $currentDateTime = Carbon::now();

        // Add 5 minutes to the current datetime
        $expiryTime = $currentDateTime->addMinutes(5);

        // DELETE the rest of the otps of that driver from the otps table
        // $success = Otp::where('driver_id', $driver->id)->forceDelete();  // this works also
        $success = $driver->otps()->forceDelete();                          // this works
        //
        if (!$success) {
            return response()->json(['message' => 'otp Deletion Failed']);
        }


        $otp = $driver->otps()->create([
            'code' => $otpCode,
            'expiry_time' => $expiryTime,
        ]);
        //
        if (!$otp) {
            return response()->json(['message' => 'OTP creation Failed'], 500);
        }

        $sendSms = SMSService::sendSms($driver->phone_number, 'Adiamat Vehicle Rental: OTP (Verification code): ' . $otpCode);

        if (!$sendSms) {
            return response()->json(['message' => 'Failed to send SMS'], 500);
        }

        return response()->json(['message' => 'SMS sent successfully'], 202);
        
    }


    public function verifyOtp(VerifyOtpDriverRequest $request)
    {
       
        $driver = Driver::where('phone_number', $request['phone_number'])->first();

        if (!$driver) {
            return response()->json(['message' => 'Login failed. Account does NOT exist.'], 404);
        }

        if ($driver->is_approved != 1) {
            return response()->json(['message' => 'Login failed. Account NOT approved.'], 401);
        }


        $isValidOtpExists = $driver->otps()->where('code', $request['code'])->exists();

        if ($isValidOtpExists == false) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        // DELETE the rest of the otps of that driver from the otps table
        // $success = Otp::where('driver_id', $driver->id)->forceDelete();  // this works also
        $success = $driver->otps()->forceDelete();                          // this works
        //
        if (!$success) {
            return response()->json(['message' => 'otp Deletion Failed']);
        }
 

        // then if all the above conditions are met ,  I will load relationships.  // like the following
        $driver->load(['address', 'media', 'vehicle']);


        // generate TOKEN
        $tokenResult = $driver->createToken('Personal Access Token', ['access-driver']);
        $expiresAt = now()->addMinutes(9950); // Set the expiration time to 50 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)
        $token = $tokenResult->accessToken;
        $token->expires_at = $expiresAt;
        $token->save();
        
        //$driver->sendEmailVerificationNotification();

        return response()->json(
            [
                'access_token' => $tokenResult->plainTextToken,
                'token_abilities' => $tokenResult->accessToken->abilities,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->accessToken->expires_at,
                'data' => new DriverResource($driver),
            ],
            200
        );


    }
    




   

    // public function loginWithFirebase(LoginDriverRequest $request)
    // {
    //    // Launch Firebase Auth
    //    $auth = app('firebase.auth');
    //    // Retrieve the Firebase credential's token
    //    $idTokenString = $request->input('firebase_token');

    //    $newlyRegistered = false;

    //    try { // Try to verify the Firebase credential token with Google

    //        $verifiedIdToken = $auth->verifyIdToken($idTokenString);
    //    } catch (\InvalidArgumentException $e) { // If the token has the wrong format

    //        return response()->json([
    //            'message' => 'Unauthorized - Can\'t parse the token: '.$e->getMessage(),
    //        ], 401);
    //    } catch (\Lcobucci\JWT\Token\InvalidTokenStructure $e) { // If the token is invalid (expired ...)

    //        return response()->json([
    //            'message' => 'Unauthorized - Token is invalid: '.$e->getMessage(),
    //        ], 401);
    //    }

    //    // Retrieve the UID (User ID) from the verified Firebase credential's token
    //    $uid = $verifiedIdToken->claims()->get('sub');

    // }



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
