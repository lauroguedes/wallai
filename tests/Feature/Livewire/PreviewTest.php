<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('renders with empty state showing logo', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->assertSet('wallpapers', [])
        ->assertSet('activeWallpaper', null)
        ->assertSet('deviceType', 'mobile')
        ->assertSeeLivewire('logo');
});

it('renders mobile mockup when device type is mobile', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->assertSeeHtml('mockup-phone');
});

it('renders desktop mockup when device type is desktop', function () {
    Livewire::test('preview', ['deviceType' => 'desktop'])
        ->assertSeeHtml('mockup-window');
});

it('loads wallpapers from session cache on mount', function () {
    $wallpapers = [
        ['id' => 'a.png', 'url' => '/storage/wallpapers/a.png', 'path' => 'wallpapers/a.png', 'extension' => 'png'],
        ['id' => 'b.png', 'url' => '/storage/wallpapers/b.png', 'path' => 'wallpapers/b.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId().':mobile', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->assertSet('wallpapers', $wallpapers)
        ->assertSet('activeWallpaper', $wallpapers[1]);
});

it('adds job to pending list when event matches device type', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123', deviceType: 'mobile')
        ->assertSet('pendingJobs', ['job-123'])
        ->assertSet('showLoading', true)
        ->assertSet('loadingPhrase', fn ($v) => ! empty($v));
});

it('ignores wallpaper-job-dispatched event for different device type', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123', deviceType: 'desktop')
        ->assertSet('pendingJobs', [])
        ->assertSet('showLoading', false);
});

it('transitions completed job to wallpapers on checkPendingJobs', function () {
    $wallpaper = ['id' => 'new.png', 'url' => '/storage/wallpapers/new.png', 'path' => 'wallpapers/new.png', 'extension' => 'png'];

    Cache::put('wallpaper_job:job-123', ['status' => 'completed', 'wallpaper' => $wallpaper], now()->addDay());
    Cache::put('wallpapers:'.session()->getId().':mobile', [$wallpaper], now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123', deviceType: 'mobile')
        ->call('checkPendingJobs')
        ->assertSet('pendingJobs', [])
        ->assertSet('activeWallpaper', $wallpaper)
        ->assertSet('showLoading', false);
});

it('handles failed job with error on checkPendingJobs', function () {
    Cache::put('wallpaper_job:job-456', [
        'status' => 'failed',
        'message' => 'Generation failed.',
    ], now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-456', deviceType: 'mobile')
        ->call('checkPendingJobs')
        ->assertSet('pendingJobs', []);
});

it('keeps pending jobs that have no result yet', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-pending', deviceType: 'mobile')
        ->call('checkPendingJobs')
        ->assertSet('pendingJobs', ['job-pending']);
});

it('selects wallpaper by index and hides loading', function () {
    $wallpapers = [
        ['id' => 'a.png', 'url' => '/storage/a.png', 'path' => 'wallpapers/a.png', 'extension' => 'png'],
        ['id' => 'b.png', 'url' => '/storage/b.png', 'path' => 'wallpapers/b.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId().':mobile', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->set('showLoading', true)
        ->call('selectWallpaper', 0)
        ->assertSet('activeWallpaper', $wallpapers[0])
        ->assertSet('showLoading', false);
});

it('shows loading when selectPendingJob is called', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->call('selectPendingJob')
        ->assertSet('showLoading', true);
});

it('deletes wallpaper from list and storage', function () {
    Storage::disk('public')->put('wallpapers/session/test.png', 'content');

    $wallpapers = [
        ['id' => 'test.png', 'url' => '/storage/test.png', 'path' => 'wallpapers/session/test.png', 'extension' => 'png'],
        ['id' => 'other.png', 'url' => '/storage/other.png', 'path' => 'wallpapers/session/other.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId().':mobile', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->call('deleteWallpaper', 'test.png')
        ->assertSet('wallpapers', fn ($v) => count($v) === 1);

    Storage::disk('public')->assertMissing('wallpapers/session/test.png');
});

it('clears active wallpaper when deleted wallpaper was active', function () {
    Storage::disk('public')->put('wallpapers/session/active.png', 'content');

    $wallpapers = [
        ['id' => 'active.png', 'url' => '/storage/active.png', 'path' => 'wallpapers/session/active.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId().':mobile', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->assertSet('activeWallpaper', $wallpapers[0])
        ->call('deleteWallpaper', 'active.png')
        ->assertSet('activeWallpaper', null);
});

it('streams download with style slug, device type, and hash in filename', function () {
    Storage::disk('public')->put('wallpapers/01JTEST1ABC.png', 'fake-image-content');

    $wallpapers = [
        ['id' => '01JTEST1ABC.png', 'url' => '/storage/wallpapers/01JTEST1ABC.png', 'path' => 'wallpapers/01JTEST1ABC.png', 'extension' => 'png', 'style' => 'photoRealist'],
    ];
    Cache::put('wallpapers:'.session()->getId().':mobile', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->call('downloadImage')
        ->assertFileDownloaded('photorealist_mobile_01JTEST1.png');

    Storage::disk('public')->assertExists('wallpapers/01JTEST1ABC.png');
});

it('streams download with desktop device type in filename', function () {
    Storage::disk('public')->put('wallpapers/01JTEST2DEF.png', 'fake-image-content');

    $wallpapers = [
        ['id' => '01JTEST2DEF.png', 'url' => '/storage/wallpapers/01JTEST2DEF.png', 'path' => 'wallpapers/01JTEST2DEF.png', 'extension' => 'png', 'style' => 'naturalLandscape'],
    ];
    Cache::put('wallpapers:'.session()->getId().':desktop', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'desktop'])
        ->call('downloadImage')
        ->assertFileDownloaded('naturallandscape_desktop_01JTEST2.png');
});

it('shows polling when pending jobs exist', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->dispatch('wallpaper-job-dispatched', jobId: 'job-123', deviceType: 'mobile')
        ->assertSeeHtml('wire:poll.5s="checkPendingJobs"');
});

it('does not show polling when no pending jobs', function () {
    Livewire::test('preview', ['deviceType' => 'mobile'])
        ->assertDontSeeHtml('wire:poll.5s="checkPendingJobs"');
});

it('shows download and delete buttons when active wallpaper exists', function () {
    $wallpapers = [
        ['id' => 'test.png', 'url' => '/storage/test.png', 'path' => 'wallpapers/test.png', 'extension' => 'png'],
    ];
    Cache::put('wallpapers:'.session()->getId().':desktop', $wallpapers, now()->addDay());

    Livewire::test('preview', ['deviceType' => 'desktop'])
        ->assertSeeHtml('wire:click="downloadImage"');
});
