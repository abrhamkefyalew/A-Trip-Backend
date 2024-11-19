<?php

return [

    'baseUrl' => env('TELEBIRR', 'https://196.188.120.3:38443/apiaccess/payment/gateway'),
    'baseUrlPay' => env('TELEBIRR', 'https://developerportal.ethiotelebirr.et:38443/payment/web/paygate'),

    'fabricAppId' => env('TELEBIRR', 'c4182ef8-9249-458a-985e-06d191f4d505'),
    'appSecret' => env('TELEBIRR', 'fad0f06383c6297f545876694b974599'),
    'merchantAppId' => env('TELEBIRR', '1309075891942408'),
    'merchantCode' => env('TELEBIRR', '76431'),
    
    'privateKey' => env('TELEBIRR_SUPER_APP_PRIVATE_KEY'),


];
