<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::call(
    fn (): bool => Cache::put('wallai:scheduler-heartbeat', now()->timestamp, now()->addMinutes(5)),
)->name('wallai:scheduler-heartbeat')
    ->everyMinute()
    ->onOneServer();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping(10);

Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('auth:clear-resets')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping();
