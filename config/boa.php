<?php

return [
    'testing' => env('BOA_TESTING', true),




    'access_key' => env('BOA_ACCESS_KEY', 'b13653780c403ab28836f1fd7547d093'),
    'testing_access_key' => env('BOA_TESTING_ACCESS_KEY', 'b13653780c403ab28836f1fd7547d093'),

    'currency' => env('BOA_CURRENCY', 'ETB'),
    'testing_currency' => env('BOA_TESTING_CURRENCY', 'ETB'),

    'locale' => env('BOA_LOCALE', 'en'),
    'testing_locale' => env('BOA_TESTING_LOCALE', 'en'),

    'profile_id' => env('BOA_PROFILE_ID', '6B8919B9-5598-4C07-950C-AAEE72F165AC'),
    'testing_profile_id' => env('BOA_TESTING_PROFILE_ID', '6B8919B9-5598-4C07-950C-AAEE72F165AC'),

    'secret_key' => env('BOA_SECRET_KEY', '8f707468b3ee47678f8d96ee425c1e63a32898506ac14217bd198c47cbe89809c810244decd04306aae257cd43647cffcb97f66c2b414455b4745b7a96ef13014e86de215fbb4d4f9531c16d082482ba4972524e0810496aa61511e919c2d45221851e63832340089bf3486d5025456de9cb7c01dd0841f6a27817a061f26b77'),
    'testing_secret_key' => env('BOA_TESTING_SECRET_KEY', '8f707468b3ee47678f8d96ee425c1e63a32898506ac14217bd198c47cbe89809c810244decd04306aae257cd43647cffcb97f66c2b414455b4745b7a96ef13014e86de215fbb4d4f9531c16d082482ba4972524e0810496aa61511e919c2d45221851e63832340089bf3486d5025456de9cb7c01dd0841f6a27817a061f26b77'),

    'transaction_type' => env('BOA_TRANSACTION_TYPE', 'sale'),
    'testing_transaction_type' => env('BOA_TESTING_TRANSACTION_TYPE', 'sale'),

    

    

    'hmac_sign_type' => env('BOA_HMAC_SIGN_TYPE', 'sha256'),

    'successful_reason_code' => env('BOA_SUCCESSFUL_REASON_CODE', 100),
    'successful_decision' => env('BOA_SUCCESSFUL_DECISION', 'ACCEPT'),
    'flagged_by_dm_reason_code' => env('BOA_FLAGGED_BY_DM_REASON_CODE', 481),

    'return_url' => env('BOA_RETURN_URL', 'https://adiamat.com/receipt'),
    'form_post_url' => env('BOA_FORM_POST_URL', 'https://secureacceptance.cybersource.com/pay'),
    'testing_form_post_url' => env('BOA_TESTING_FORM_POST_URL', 'https://testsecureacceptance.cybersource.com/pay'),

];
