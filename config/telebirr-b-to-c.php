<?php

return [
    'testing' => env('TELEBIRR_BTOC_TESTING', true),


    

    'request_url' => env('TELEBIRR_BTOC_REQUEST_URL', 'http://10.180.70.177:30001/payment/services/APIRequestMgrService'),                     //
    'request_url_testing' => env('TELEBIRR_BTOC_REQUEST_URL_TESTING', 'http://10.180.79.13:30001/payment/services/APIRequestMgrService'),      // 


    'result_url' => env('TELEBIRR_BTOC_RESULT_URL', 'http://api.adiamat.et:6080/api/payment/telebirr_callback/'),                              // kind of callback
    'result_url_testing' => env('TELEBIRR_BTOC_RESULT_URL_TESTING', 'https://www.fms.toethiotravel.com/api/payment/telebirrCallback/'),        // kind of callback

    
    'third_party_id' => env('TELEBIRR_BTOC_THIRD_PARTY_ID', 'AdiamatTrading'),
    'third_party_id_testing' => env('TELEBIRR_BTOC_THIRD_PARTY_ID_TESTING', 'Adiamat'),


    'password' => env('TELEBIRR_BTOC_PASSWORD', '+P0dZnDwl61Hx+D5EhDKtwZOyV9vfymkhx5TMDjQyx4='),
    'password_testing' => env('TELEBIRR_BTOC_PASSWORD_TESTING', '9idzVEwviq/1rjsPdkERkAs6yy2/Jpw+3fUnpZHeRCY='),


    'identifier' => env('TELEBIRR_BTOC_IDENTIFIER', '5133611'),
    'identifier_testing' => env('TELEBIRR_BTOC_IDENTIFIER_TESTING', '22050701'),


    'security_credential' => env('TELEBIRR_BTOC_SECURITY_CREDENTIAL', 'PGZKqOv64CxUWIO9QW2320N+I9de3SJDid+BQhmT88g='),
    'security_credential_testing' => env('TELEBIRR_BTOC_SECURITY_CREDENTIAL_TESTING', 'jWSYF5O9GVU9CzuZ77KR+iloNS0/ryuGluPRxXyhWfw='),


    'short_code' => env('TELEBIRR_BTOC_SHORT_CODE', '513361'),
    'short_code_testing' => env('TELEBIRR_BTOC_SHORT_CODE_TESTING', '220507'),


];
