<?php

return [
    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost:5173,127.0.0.1:5173',
    )),

    'guard' => ['web'],

    // Expire Bearer tokens after 12 hours unless an environment overrides it.
    'expiration' => env('SANCTUM_EXPIRATION', 720),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'last_used_at' => true,

];
