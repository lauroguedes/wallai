<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | These providers are used when the current browser session has not saved
    | its own provider preferences. Both OpenAI and Gemini support the text
    | and image generation capabilities used by this application.
    |
    */

    'ai' => [
        'text_provider' => env('WALLPAPER_TEXT_PROVIDER', 'gemini'),
        'image_provider' => env('WALLPAPER_IMAGE_PROVIDER', 'gemini'),
        'models' => [
            'openai' => [
                'text' => [
                    'default' => 'gpt-5.4',
                    'options' => [
                        ['id' => 'gpt-5.4', 'name' => 'GPT-5.4 (SDK default)'],
                        ['id' => 'gpt-5.4-nano', 'name' => 'GPT-5.4 Nano (economy)'],
                        ['id' => 'gpt-5.4-pro', 'name' => 'GPT-5.4 Pro (highest capability)'],
                    ],
                ],
                'image' => [
                    'default' => 'gpt-image-2',
                    'options' => [
                        ['id' => 'gpt-image-2', 'name' => 'GPT Image 2 (SDK default)'],
                    ],
                ],
            ],
            'gemini' => [
                'text' => [
                    'default' => 'gemini-3.7-flash',
                    'options' => [
                        ['id' => 'gemini-3.7-flash', 'name' => 'Gemini 3.7 Flash (SDK default)'],
                        ['id' => 'gemini-3.5-flash-lite', 'name' => 'Gemini 3.5 Flash-Lite (economy)'],
                    ],
                ],
                'image' => [
                    'default' => 'gemini-3.1-flash-image-preview',
                    'options' => [
                        ['id' => 'gemini-3.1-flash-image-preview', 'name' => 'Gemini 3.1 Flash Image Preview (SDK default)'],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Processes
    |--------------------------------------------------------------------------
    |
    | The maximum number of concurrent wallpaper generation processes allowed
    | per session. This also limits how many pending jobs a user can have
    | at any given time.
    |
    */

    'queue_processes' => (int) env('WALLPAPER_QUEUE_PROCESSES', 3),

];
