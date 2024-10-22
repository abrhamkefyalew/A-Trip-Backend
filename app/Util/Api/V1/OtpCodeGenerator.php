<?php

namespace App\Util\Api\V1;


class OtpCodeGenerator 
{
    public static function generate($length = 6)
    {
        $pool = '0123456789';

        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }
}