<?php

use App\Notifications\UserInvitation;

it('routes invitation mail to the dedicated notification queue', function () {
    $notification = new UserInvitation('invitation-token', 'Admin User');

    expect($notification->viaQueues())->toBe([
        'mail' => 'notifications',
    ]);
});

it('configures dedicated notification workers and safe wallpaper timeouts', function () {
    expect(config('horizon.defaults.supervisor-notifications.connection'))->toBe('redis')
        ->and(config('horizon.defaults.supervisor-notifications.queue'))->toBe(['notifications'])
        ->and(config('horizon.defaults.supervisor-notifications.timeout'))->toBe(60)
        ->and(config('horizon.defaults.supervisor-wallpapers.timeout'))->toBeGreaterThan(180)
        ->and(config('queue.connections.redis.retry_after'))
        ->toBeGreaterThan(config('horizon.defaults.supervisor-wallpapers.timeout'));
});
