<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'replicate' => [
        'key' => env('REPLICATE_API_KEY'),
        'image_generator_model' => env('REPLICATE_IMAGE_GENERATOR_MODEL', 'black-forest-labs/flux-schnell'),
        'text_generator_model' => env('REPLICATE_TEXT_GENERATOR_MODEL', 'meta/meta-llama-3-8b-instruct'),
        'aspect_ratio' => env('REPLICATE_ASPECT_RATIO', '9:21'),
        'output_format' => env('REPLICATE_OUTPUT_FORMAT', 'webp'),
    ],
];
