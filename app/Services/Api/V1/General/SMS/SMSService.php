<?php

namespace App\Services\Api\V1\General\SMS;

use Illuminate\Support\Facades\Http;

class SMSService
{
    public static function sendSms($phoneNo, $message)
    {
        // hardcoded sms url 
        $url = "http://melekt.com/sms/web/api";

        // Sample phone number
        // "912345678"; // Example where the string starts with 9 and has a length of 9
        // "701234567"; // Example where the string starts with 7 and has a length of 9
        // "0123456789"; // Example where the string starts with 0 and has a length of 10


        // Check if the phone number is 10 characters long
        if (strlen($phoneNo) == 10) {
            $ptn = "/^0/";  // Regex pattern to match leading '0'
            $rpltxt = "+251";  // Replacement text
            $phoneNo = preg_replace($ptn, $rpltxt, $phoneNo);  // Replace leading '0' with '+251'
        } // Check if the phone number is 9 characters long
        elseif (strlen($phoneNo) == 9) {
            if ($phoneNo[0] == '9' || $phoneNo[0] == '7') {
                $phoneNo = "+251" . $phoneNo; // Append '+251' as a prefix
            }
        }



        $url = config('smsgeez.url');

        $body = [
            'token' => config('smsgeez.token'),
            'phone' => $phoneNo,
            'msg' => $message,
        ];


        // $response = Http::post($url, $body);
        $response = Http::timeout(60)
            // ->withOptions(['verify' => false]) // false - is for NON secure environments
            ->post($url, $body);

        if ($response->successful()) {
            return true;
        }

        return false;
    }
}