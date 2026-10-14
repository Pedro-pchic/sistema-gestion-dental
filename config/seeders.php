<?php

return [
    'initial_admin' => [
        'name' => env('DENTAL_ADMIN_NAME'),
        'email' => env('DENTAL_ADMIN_EMAIL'),
        'password' => env('DENTAL_ADMIN_PASSWORD'),
    ],
    'dental_demo' => [
        'enabled' => env('DENTAL_DEMO_ENABLED', false),
        'database' => env('DENTAL_DEMO_DATABASE'),
        'password' => env('DENTAL_DEMO_PASSWORD'),
        'allow_neondb' => env('DENTAL_DEMO_ALLOW_NEONDB', false),
        'neondb_host' => env('DENTAL_DEMO_NEONDB_HOST'),
    ],
];
