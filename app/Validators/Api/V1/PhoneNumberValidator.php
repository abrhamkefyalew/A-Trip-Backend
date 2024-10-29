<?php

namespace App\Validators\Api\V1;

use Illuminate\Support\Facades\Validator;

class PhoneNumberValidator
{
    public function formatAndValidatePhoneNumber($value)
    {
        if (strlen($value) == 10) {
            if ($value[0] == '0') {
                // If the number is 10 digits long and starts with '0', it replaces the leading '0' with '+251
                $ptn = "/^0/";
                $rpltxt = "+251";
                $value = preg_replace($ptn, $rpltxt, $value);
            }
            else {
                // Use Laravel's validation mechanism to return an error
                // If the number is 10 digits long but does not start with '0', 
                // it validates that it should start with '0' using Laravel's validation mechanism
                $validator = Validator::make(['phone_number' => $value], [
                    'phone_number' => 'starts_with:0', 
                ]);
    
                if ($validator->fails()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        } 
        elseif (strlen($value) == 9) {
            $value = "+251" . $value;
            
        }
        elseif (strlen($value) == 12) {
            $value = "+" . $value;
        }
        else {
            // Use Laravel's validation mechanism to return an error
            // If the number does not fall into the above scenarios (9, 10, or 12 digits), 
            // it validates the number's length against 13 digits using Laravel's validation mechanism.
            $validator = Validator::make(['phone_number' => $value], [
                'phone_number' => 'size:13',
            ]);
    
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }


            // 
            if (strlen($value) == 13) {
                // Use Laravel's validation mechanism to return an error
                // If the number is 13 digits long but does not start with '+', 
                // it validates that it should start with '+' using Laravel's validation mechanism
                $validator = Validator::make(['phone_number' => $value], [
                    'phone_number' => 'starts_with:+', 
                ]);
    
                if ($validator->fails()) {
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        }



        return $value;
    }
}