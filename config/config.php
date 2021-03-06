<?php

return [

    /*
    |--------------------------------------------------------------------------
    | authentication endpoint
    |--------------------------------------------------------------------------
    | used to form url used to fetch a new token
    */
    'authentication_endpoint' => env("MACHINA_CLIENT_AUTHENTICATION_ENDPOINT", "auth/login"),
    'token_result_key' => env("MACHINA_CLIENT_TOKEN_RESULT_KEY", "access_token"),
];
