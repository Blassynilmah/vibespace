<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // 👇 Change this to your actual Vite dev server origin
    'allowed_origins' => ['http://localhost:5173', 'http://localhost:8000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Needed for Sanctum SPA cookie auth
    'supports_credentials' => true,

];