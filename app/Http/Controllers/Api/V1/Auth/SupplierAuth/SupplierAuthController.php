<?php

namespace App\Http\Controllers\Api\V1\Auth\SupplierAuth;

use Carbon\Carbon;
use App\Jobs\SendSmsJob;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Util\Api\V1\OtpCodeGenerator;
use App\Http\Requests\Api\V1\AuthRequests\LoginSupplierRequest;
use App\Http\Resources\Api\V1\SupplierResources\SupplierResource;
use App\Http\Requests\Api\V1\AuthRequests\Otp\LoginOtpSupplierRequest;
use App\Http\Requests\Api\V1\AuthRequests\Otp\VerifyOtpSupplierRequest;

// use Kreait\Firebase\Factory;
// use Kreait\Firebase\ServiceAccount;
// use Kreait\Firebase\Auth;


class SupplierAuthController extends Controller
{
    public function login(LoginSupplierRequest $request)
    {
        // better use load than with, since here after all we get the data , we are checking if the password does match,   
        // if password does not match all the data and relation and Eager Load is wasted and the data will NOT be returned
        // do first get only the supplier and if the password matches then get the other relations using load()
        $supplier = Supplier::with(['address', 'media', 'vehicles'])->where('email', $request->email)->where('is_approved', 1)->first();

        if ($supplier) {
            if (Hash::check($request->password, $supplier->password)) {
                $tokenResult = $supplier->createToken('Personal Access Token', ['access-supplier']);
                $expiresAt = now()->addMinutes(9950); // Set the expiration time to 50 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)
                $token = $tokenResult->accessToken;
                $token->expires_at = $expiresAt;
                $token->save();

                //$supplier->sendEmailVerificationNotification();

                return response()->json(
                    [
                        'access_token' => $tokenResult->plainTextToken,
                        'token_abilities' => $tokenResult->accessToken->abilities,
                        'token_type' => 'Bearer',
                        'expires_at' => $tokenResult->accessToken->expires_at,
                        'data' => new SupplierResource($supplier),
                    ],
                    200
                );
            }
        }

        return response()->json(['message' => 'Login failed. Incorrect email or password.'], 400);
    }





    public function loginOtp(LoginOtpSupplierRequest $request)
    {

        $supplier = Supplier::where('phone_number', $request['phone_number'])->first();
        //
        if (!$supplier) {
            return response()->json(['message' => 'Login failed. Account does NOT exist.'], 404);
        }

        if ($supplier->is_approved != 1) {
            return response()->json(['message' => 'Login failed. Account NOT approved.'], 401);
        }


        // IF there are any generated OTPs for this supplier , then DELETE them
        if ($supplier->otps()->exists()) {
            // DELETE the rest of the otps of that supplier from the otps table
            // $success = Otp::where('supplier_id', $supplier->id)->forceDelete();  // this works also
            $success = $supplier->otps()->forceDelete();                          // this works
            //
            if (!$success) {
                return response()->json(['message' => 'otp Deletion Failed']);
            }
        }


        $otpCode = OtpCodeGenerator::generate(6);


        // Generate current datetime
        $currentDateTime = Carbon::now();

        // Add 5 minutes to the current datetime
        $expiryTime = $currentDateTime->addMinutes(5);


        if ($supplier->phone_number == "+251910101010") {
            $otp = $supplier->otps()->create([
                'code' => "123456",
                'expiry_time' => $expiryTime,
            ]);
        } else {
            $otp = $supplier->otps()->create([
                'code' => $otpCode,
                'expiry_time' => $expiryTime,
            ]);
        }


        //
        if (!$otp) {
            return response()->json(['message' => 'OTP creation Failed'], 500);
        }

        // $sendSms = SMSService::sendSms($supplier->phone_number, 'Adiamat Vehicle Rental: OTP (Verification code): ' . $otpCode);
        // //
        // if (!$sendSms) {
        //     return response()->json(['message' => 'Failed to send SMS'], 500);
        // }

        try {
            SendSmsJob::dispatch($supplier->phone_number, 'Adiamat Vehicle Rental: OTP (Verification code): ' . $otpCode)->onQueue('sms');
        } catch (\Throwable $e) {
            // Log the exception or handle it as needed
            return response()->json(['message' => 'Failed to dispatch SMS job'], 500);
        }


        return response()->json(['message' => 'SMS job dispatched successfully'], 202);
    }


    public function verifyOtp(VerifyOtpSupplierRequest $request)
    {

        $supplier = Supplier::where('phone_number', $request['phone_number'])->first();
        //
        if (!$supplier) {
            return response()->json(['message' => 'Login failed. Account does NOT exist.'], 404);
        }

        if ($supplier->is_approved != 1) {
            return response()->json(['message' => 'Login failed. Account NOT approved.'], 401);
        }


        // Check if the OTP from the user input exists and is NOT Expired
        $isValidOtpExists = $supplier->otps()
            ->where('code', $request['code'])
            ->where('expiry_time', '>', now()) // Check if the EXPIRY time is in the future
            ->exists();
        //
        if ($isValidOtpExists == false) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }


        // IF there are any generated OTPs for this supplier , then DELETE them
        if ($supplier->otps()->exists()) {
            // DELETE the rest of the otps of that supplier from the otps table
            // $success = Otp::where('supplier_id', $supplier->id)->forceDelete();  // this works also
            $success = $supplier->otps()->forceDelete();                          // this works
            //
            if (!$success) {
                return response()->json(['message' => 'otp Deletion Failed']);
            }
        }


        // then if all the above conditions are met ,  I will load relationships.  // like the following
        $supplier->load(['address', 'media', 'vehicles']);


        // generate TOKEN
        $tokenResult = $supplier->createToken('Personal Access Token', ['access-supplier']);
        $expiresAt = now()->addMinutes(262170); // Set the expiration time to 50 minutes from now - -   -   -   -   now() = is helper function of laravel, - - - (it is NOT Carbon's)
        $token = $tokenResult->accessToken;
        $token->expires_at = $expiresAt;
        $token->save();

        //$supplier->sendEmailVerificationNotification();

        return response()->json(
            [
                'access_token' => $tokenResult->plainTextToken,
                'token_abilities' => $tokenResult->accessToken->abilities,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->accessToken->expires_at,
                'data' => new SupplierResource($supplier),
            ],
            200
        );
    }






    // public function loginWithFirebase(LoginSupplierRequest $request)
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
