<?php

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\Console\Tester\CommandTester;

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

it('only allows active administrators to view Horizon', function () {
    $administrator = new User(['is_admin' => true, 'is_active' => true]);
    $regularUser = new User(['is_admin' => false, 'is_active' => true]);
    $inactiveAdministrator = new User(['is_admin' => true, 'is_active' => false]);

    expect(Gate::forUser($administrator)->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser($regularUser)->allows('viewHorizon'))->toBeFalse()
        ->and(Gate::forUser($inactiveAdministrator)->allows('viewHorizon'))->toBeFalse();
});

it('schedules Horizon metrics and the runtime heartbeat', function () {
    $tester = new CommandTester(Artisan::all()['schedule:list']);

    expect($tester->execute([]))->toBe(0)
        ->and($tester->getDisplay())->toContain('horizon:snapshot')
        ->toContain('wallai:scheduler-heartbeat');
});
