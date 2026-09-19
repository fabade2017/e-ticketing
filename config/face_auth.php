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
        'aws' => [
            'region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
            'collection_id' => env('AWS_REKOGNITION_COLLECTION', 'na-personnel'),
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
