<?php

return [
    'signature' => env('GLIDE_SIGNATURE'),
    'source' => [
        'driver'   => 's3',
        'key'      => env('AWS_ACCESS_KEY_ID'),
        'secret'   => env('AWS_SECRET_ACCESS_KEY'),
        'region'   => env('AWS_REGION'),
        'bucket'   => env('AWS_BUCKET'),
        'url'      => env('AWS_S3_URL', ''),
        'options'  => [],
    ],
    'cache' => [
        'driver' => 'local',
    ],
];
