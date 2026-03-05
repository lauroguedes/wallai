<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('renders with empty state showing logo', function () {
    Livewire::test('preview')
        ->assertSet('wallpapers', [])
        ->assertSet('activeWallpaper', null)
        ->assertSeeLivewire('logo');
});

it('renders with mobile device type by default', function () {
    Livewire::test('preview')
        ->assertSet('deviceType', 'mobile')
        ->assertSeeHtml('mockup-phone');
});

it('switches to desktop view when setDeviceType is called', function () {
    Livewire::test('preview')
        ->call('setDeviceType', 'desktop')
        ->assertSet('deviceType', 'desktop')
        ->assertSeeHtml('mockup-window');
});

it('dispatches device-type-changed event on toggle', function () {
    Livewire::test('preview')
        ->call('setDeviceType', 'desktop')
        ->assertDispatched('device-type-changed', 'desktop');
});

it('loads wallpapers from session cache on mount', function () {
    $wallpapers = [
        ['id' => 'a.png', 'url' => '/storage/wallpapers/a.png', 'path' => 'wallpapers/a.png', 'extension' => 'png'],
        ['id' => 'b.png', 'url' => '/storage/wallpapers/b.png', 'path' => 'wallpapers/b.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->assertSet('wallpapers', $wallpapers)
        ->assertSet('activeWallpaper', $wallpapers[1]);
});

it('adds job to pending list on wallpaper-job-dispatched event', function () {
    Livewire::test('preview')
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123')
        ->assertSet('pendingJobs', ['job-123'])
        ->assertSet('loadingPhrase', fn ($v) => ! empty($v));
});

it('transitions completed job to wallpapers on checkPendingJobs', function () {
    $wallpaper = ['id' => 'new.png', 'url' => '/storage/wallpapers/new.png', 'path' => 'wallpapers/new.png', 'extension' => 'png'];

    Cache::put('wallpaper_job:job-123', ['status' => 'completed', 'wallpaper' => $wallpaper], now()->addDay());
    Cache::put('wallpapers:'.session()->getId(), [$wallpaper], now()->addDay());

    Livewire::test('preview')
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123')
        ->call('checkPendingJobs')
        ->assertSet('pendingJobs', [])
        ->assertSet('activeWallpaper', $wallpaper);
});

it('handles failed job with error on checkPendingJobs', function () {
    Cache::put('wallpaper_job:job-456', [
        'status' => 'failed',
        'message' => 'Generation failed.',
    ], now()->addDay());

    Livewire::test('preview')
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-456')
        ->call('checkPendingJobs')
        ->assertSet('pendingJobs', []);
});

it('keeps pending jobs that have no result yet', function () {
    Livewire::test('preview')
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-pending')
        ->call('checkPendingJobs')
        ->assertSet('pendingJobs', ['job-pending']);
});

it('selects wallpaper by index', function () {
    $wallpapers = [
        ['id' => 'a.png', 'url' => '/storage/a.png', 'path' => 'wallpapers/a.png', 'extension' => 'png'],
        ['id' => 'b.png', 'url' => '/storage/b.png', 'path' => 'wallpapers/b.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->call('selectWallpaper', 0)
        ->assertSet('activeWallpaper', $wallpapers[0]);
});

it('deletes wallpaper from list and storage', function () {
    Storage::disk('public')->put('wallpapers/session/test.png', 'content');

    $wallpapers = [
        ['id' => 'test.png', 'url' => '/storage/test.png', 'path' => 'wallpapers/session/test.png', 'extension' => 'png'],
        ['id' => 'other.png', 'url' => '/storage/other.png', 'path' => 'wallpapers/session/other.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->call('deleteWallpaper', 'test.png')
        ->assertSet('wallpapers', fn ($v) => count($v) === 1);

    Storage::disk('public')->assertMissing('wallpapers/session/test.png');
});

it('clears active wallpaper when deleted wallpaper was active', function () {
    Storage::disk('public')->put('wallpapers/session/active.png', 'content');

    $wallpapers = [
        ['id' => 'active.png', 'url' => '/storage/active.png', 'path' => 'wallpapers/session/active.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->assertSet('activeWallpaper', $wallpapers[0])
        ->call('deleteWallpaper', 'active.png')
        ->assertSet('activeWallpaper', null);
});

it('streams download without deleting file', function () {
    Storage::disk('public')->put('wallpapers/test-id.png', 'fake-image-content');

    $wallpapers = [
        ['id' => 'test-id.png', 'url' => '/storage/wallpapers/test-id.png', 'path' => 'wallpapers/test-id.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->call('downloadImage')
        ->assertFileDownloaded('phone_wallpaper.png');

    Storage::disk('public')->assertExists('wallpapers/test-id.png');
});

it('streams download with desktop filename when device type is desktop', function () {
    Storage::disk('public')->put('wallpapers/test-id.png', 'fake-image-content');

    $wallpapers = [
        ['id' => 'test-id.png', 'url' => '/storage/wallpapers/test-id.png', 'path' => 'wallpapers/test-id.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->call('setDeviceType', 'desktop')
        ->call('downloadImage')
        ->assertFileDownloaded('desktop_wallpaper.png');
});

it('shows download and delete buttons when active wallpaper exists', function () {
    $wallpapers = [
        ['id' => 'test.png', 'url' => '/storage/test.png', 'path' => 'wallpapers/test.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId(), $wallpapers, now()->addDay());

    Livewire::test('preview')
        ->assertSeeHtml('wire:click="downloadImage"')
        ->assertSeeHtml('wire:click="deleteWallpaper');
});

it('shows loading phrase when pending jobs exist', function () {
    Livewire::test('preview')
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123')
        ->assertSeeHtml('wire:poll.5s="checkPendingJobs"');
});

it('does not show polling when no pending jobs', function () {
    Livewire::test('preview')
        ->assertDontSeeHtml('wire:poll.5s="checkPendingJobs"');
});
