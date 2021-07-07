<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled grant types
    |--------------------------------------------------------------------------
    |
    | This array of user provider class indexed by grant type will be enabled
    | when application is started.
    |
    */

    'grants' => [
        // 'otp_grant' => 'App\Passport\OTPGrantProvider',
    ],
    
    'access_token' =>[
        'lifetime' => 1 // In hour
    ],
    
    'refresh_token' =>[
        'lifetime' => 90 // In days
    ]

];
