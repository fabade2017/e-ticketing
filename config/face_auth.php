<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Face Authentication Mode
    |--------------------------------------------------------------------------
    | Modes: 'local' (on-premise / face-api.js), 'cloud' (AWS Rekognition / Azure Face)
    | SuperAdmin can toggle via SystemSetting `face_auth_mode`.
    | Env FACE_AUTH_MODE is the default seed.
    */
    'mode' => env('FACE_AUTH_MODE', 'local'),

    'threshold' => env('FACE_AUTH_THRESHOLD', 0.6),

    'cloud' => [
        'provider' => env('FACE_CLOUD_PROVIDER', 'aws'), // aws|azure

        /*
        | Cloud match threshold. AWS Rekognition CompareFaces returns a
        | percentage (0-100); anything at/above this counts as a match.
        */
        'threshold' => env('FACE_CLOUD_THRESHOLD', 80),

        'aws' => [
            'region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
            'collection_id' => env('AWS_REKOGNITION_COLLECTION', 'na-personnel'),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'session_token' => env('AWS_SESSION_TOKEN'),
        ],
        'azure' => [
            'endpoint' => env('AZURE_FACE_ENDPOINT'),
            'key' => env('AZURE_FACE_KEY'),
        ],
    ],

    'local' => [
        'model_path' => env('FACE_LOCAL_MODEL_PATH', '/models/face-api'),
    ],

    'liveness_required' => env('FACE_LIVENESS_REQUIRED', true),
];
