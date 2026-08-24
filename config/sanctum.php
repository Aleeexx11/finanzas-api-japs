<?php

return [
    /*
    | Sanctum is used only for personal access tokens in this API.
    | Session guards and stateful SPA domains are intentionally disabled.
    */
    'stateful' => [],

    'guard' => [],

    'expiration' => env('SANCTUM_EXPIRATION'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'last_used_at' => true,

    /* Disable Sanctum's cookie/CSRF endpoint. */
    'routes' => false,
];
