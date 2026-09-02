<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Pengaturan CORS untuk menghubungkan backend Laravel dengan frontend React.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie','apiv1/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:3000,http://localhost:5173,http://192.168.158.235:8080,http://localhost:8000,http://192.168.147.199:8000'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
