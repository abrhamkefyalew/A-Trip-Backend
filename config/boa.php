<?php

return [
    'testing' => env('BOA_TESTING', true),

    'profile_id' => env('BOA_PROFILE_ID', 'c4182ef8-9249-458a-985e-06d191f4d505'),
    // 'testing_profile_id' => env('BOA_TESTING_PROFILE_ID', '2CDB88AB-F075-4174-BA5F-1EEA41AA4F49'),
    // 'testing_profile_id' => env('BOA_TESTING_PROFILE_ID', 'F256CDF3-52FC-4DBB-996C-4D4A092811F9'),
    // 'testing_profile_id' => env('BOA_TESTING_PROFILE_ID', '4F4C4BB5-78AD-438D-962F-7B9762727774'),
    'testing_profile_id' => env('BOA_TESTING_PROFILE_ID', '3CA75658-2055-4370-A878-EFA205F450C0'),

    'access_key' => env('BOA_ACCESS_KEY', 'c4182ef8-9249-458a-985e-06d191f4d505'),
    // 'testing_access_key' => env('BOA_TESTING_ACCESS_KEY', '62fa9bde20a931f581583f50aeedcb10'),
    // 'testing_access_key' => env('BOA_TESTING_ACCESS_KEY', 'e4bdf48b3e8f310987d648af7e479238'),
    // 'testing_access_key' => env('BOA_TESTING_ACCESS_KEY', '5f5afece27553262a34e9d4c6c3049c8'),
    'testing_access_key' => env('BOA_TESTING_ACCESS_KEY', '6ac22d2be658391f88cf5b7296074ecf'),

    'secret_key' => env('BOA_SECRET_KEY', 'c4182ef8-9249-458a-985e-06d191f4d505'),
    // 'testing_secret_key' => env('BOA_TESTING_SECRET_KEY', '4078b9ba33a84bcf87416b85890c8bdc27febfda50984a9eada2af6e119b1ed4652cb02de10b4709a30866f76918a4fb2663ba12eb0c4c9e851196ceb0b9580c5e1c887a9a64430b8958d50410fedb7f0c7f02c879314522a83d84a06c531cbf25b9ec9b31be4a16b2a2804528fa1e0c588a3d37eda24679a2cdb52fd89bda76'),
    // 'testing_secret_key' => env('BOA_TESTING_SECRET_KEY', '57d1c64042e74c4b83a5be84daecd06c0efbf1dfc49343e48f024a37e2d422c54de44256c10d4edeafb408bfcc6c954f6772bac8b09f42d7a2560a29c9a155ba370649cff99f41b1a9542495bced6bbeb38d252fba734fc895f6a1f079115e54467f5db1d1394d2ab246b81444f45948ef5eb5b0913d463e8a98315167e989df'),
    // 'testing_secret_key' => env('BOA_TESTING_SECRET_KEY', '1e2ced1e9f6048dba8104738c26dbae532e62d8e54e44cc690d9d09123cc04efb08304bc8fc1414cada3c2ee06a1cb711a7bff7f23a44859931ffebf9678a5d154d33e5dbc4446ad989692da76c4a98c230f3d909667442eb2d3788bff9fc4871374846b3f30422cb6d1450e875abab8490213f2ece6473ba55a4bb24ef0f9d8'),
    'testing_secret_key' => env('BOA_TESTING_SECRET_KEY', '8f707468b3ee47678f8d96ee425c1e63a32898506ac14217bd198c47cbe89809c810244decd04306aae257cd43647cffcb97f66c2b414455b4745b7a96ef13014e86de215fbb4d4f9531c16d082482ba4972524e0810496aa61511e919c2d45221851e63832340089bf3486d5025456de9cb7c01dd0841f6a27817a061f26b77'),

    'transaction_type' => env('BOA_TRANSACTION_TYPE', 'authorization'),
    'testing_transaction_type' => env('BOA_TESTING_TRANSACTION_TYPE', 'authorization'),

    'currency' => env('BOA_CURRENCY', 'USD'),
    'testing_currency' => env('BOA_TESTING_CURRENCY', 'ETB'),

    'locale' => env('BOA_LOCALE', 'en'),
    'testing_locale' => env('BOA_TESTING_LOCALE', 'en'),

    'hmac_sign_type' => env('BOA_HMAC_SIGN_TYPE', 'sha256'),

    'successful_reason_code' => env('BOA_SUCCESSFUL_REASON_CODE', 100),
    'successful_decision' => env('BOA_SUCCESSFUL_DECISION', 'ACCEPT'),
    'flagged_by_dm_reason_code' => env('BOA_FLAGGED_BY_DM_REASON_CODE', 481),

    'return_url' => env('BOA_RETURN_URL', 'https://seregelagebeya.com/receipt'),
    'form_post_url' => env('BOA_FORM_POST_URL', 'https://secureacceptance.cybersource.com/pay'),
    'testing_form_post_url' => env('BOA_FORM_POST_URL', 'https://testsecureacceptance.cybersource.com/pay'),

];
