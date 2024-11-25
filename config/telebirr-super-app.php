<?php

return [
    'testing' => env('TELEBIRR_TESTING', false),




    'baseUrl' => env('TELEBIRR_BASE_URL', 'https://196.188.120.5:38443/apiaccess/payment/gateway'),
    'baseUrl_testing' => env('TELEBIRR_BASE_URL_TESTING', 'https://196.188.120.3:38443/apiaccess/payment/gateway'),

    'baseUrlPay' => env('TELEBIRR_BASE_URL_PAY', 'https://superapp.ethiomobilemoney.et:38443/payment/web/paygate'),
    'baseUrlPay_testing' => env('TELEBIRR_BASE_URL_PAY_TESTING', 'https://developerportal.ethiotelebirr.et:38443/payment/web/paygate'),



    'fabricAppId' => env('TELEBIRR_FABRIC_APP_ID', 'ea8b06fd-7d1e-40f7-9624-c06d5a5280ad'),
    'fabricAppId_testing' => env('TELEBIRR_FABRIC_APP_ID_TESTING', 'c4182ef8-9249-458a-985e-06d191f4d505'),

    'appSecret' => env('TELEBIRR_APP_SECRET', '41559b842f386c78ca70d3db03b75e71'),
    'appSecret_testing' => env('TELEBIRR_APP_SECRET_TESTING', 'fad0f06383c6297f545876694b974599'),

    'merchantAppId' => env('TELEBIRR_MERCHANT_APP_ID', '1194619669504001'),
    'merchantAppId_testing' => env('TELEBIRR_MERCHANT_APP_ID_TESTING', '1309075891942408'),

    'merchantCode' => env('TELEBIRR_MERCHANT_CODE', '513361'),
    'merchantCode_testing' => env('TELEBIRR_MERCHANT_CODE_TESTING', '76431'),
    
    'privateKey' => env('TELEBIRR_SUPER_APP_PRIVATE_KEY'),
    'privateKey_testing' => env('TELEBIRR_SUPER_APP_PRIVATE_KEY_TESTING'),


];
