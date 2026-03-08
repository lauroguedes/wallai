<?php

return [

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
