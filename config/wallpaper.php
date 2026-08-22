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
