<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        // this one is for the default laravel User // but check first 
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],


        // I WILL DEFINE PROVIDERS BELOW FOR EACH GUARDS I DEFINE HERE

        // this is because i did not replace the laravel default User Model by my own Admin Model. i added my new Admin Model. 
        // so i need to add this admin guard
        'admin' => [
            'provider' => 'admins',
        ],


        // the following users guards will be usable when their Models are created
        // // so are commented temporarily // abrham remember to uncomment later when their Models are created
        // 'organization_user' => [
        //     'provider' => 'organization_users',
        // ],
        // 'customer' => [
        //     'provider' => 'customers',
        // ],
        // 'supplier' => [
        //     'provider' => 'suppliers',
        // ],
        // 'driver' => [
        //     'provider' => 'drivers',
        // ],


    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],

        
        // THESE ARE THE PROVIDERS FOR THE GUARDS DEFINED ABOVE

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        // the following users providers will be usable when their Models are created
        // // so are commented temporarily // abrham remember to uncomment later when their Models are created
        // 'organization_users' => [
        //     'driver' => 'eloquent',
        //     'model' => App\Models\OrganizationUser::class,
        // ],
        // 'customers' => [
        //     'driver' => 'eloquent',
        //     'model' => App\Models\Customer::class,
        // ],
        // 'suppliers' => [
        //     'driver' => 'eloquent',
        //     'model' => App\Models\Supplier::class,
        // ],
        // 'drivers' => [
        //     'driver' => 'eloquent',
        //     'model' => App\Models\Driver::class,
        // ],

        
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],


        // THE FOLLOWING SETTINGS ARE DEFINED FOR THE ABOVE PROVIDERS

        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        // the following settings for the providers will be usable when their Models are created
        // // so are commented temporarily // abrham remember to uncomment later when their Models are created
        // 'organization_users' => [
        //     'provider' => 'organization_users',
        //     'table' => 'password_reset_tokens',
        //     'expire' => 60,
        //     'throttle' => 60,
        // ],
        // 'customers' => [
        //     'provider' => 'customers',
        //     'table' => 'password_reset_tokens',
        //     'expire' => 60,
        //     'throttle' => 60,
        // ],
        // 'suppliers' => [
        //     'provider' => 'suppliers',
        //     'table' => 'password_reset_tokens',
        //     'expire' => 60,
        //     'throttle' => 60,
        // ],
        // 'drivers' => [
        //     'provider' => 'drivers',
        //     'table' => 'password_reset_tokens',
        //     'expire' => 60,
        //     'throttle' => 60,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

];
