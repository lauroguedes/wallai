<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders with empty state showing logo', function () {
    Livewire::test('preview')
        ->assertSet('wallpapers', ['mobile' => null, 'desktop' => null])
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

it('stores wallpaper under current device type', function () {
    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->assertSet('wallpapers.mobile', $wallpaperData)
        ->assertSet('wallpapers.desktop', null);
});

it('isolates wallpapers per device type', function () {
    $mobileWallpaper = [
        'id' => 'mobile.png',
        'url' => '/storage/wallpapers/mobile.png',
        'path' => 'wallpapers/mobile.png',
        'extension' => 'png',
    ];

    $desktopWallpaper = [
        'id' => 'desktop.png',
        'url' => '/storage/wallpapers/desktop.png',
        'path' => 'wallpapers/desktop.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $mobileWallpaper)
        ->assertSet('wallpapers.mobile', $mobileWallpaper)
        ->call('setDeviceType', 'desktop')
        ->assertSet('wallpapers.desktop', null)
        ->dispatch('wallpaper-generated', $desktopWallpaper)
        ->assertSet('wallpapers.desktop', $desktopWallpaper)
        ->call('setDeviceType', 'mobile')
        ->assertSet('wallpapers.mobile', $mobileWallpaper);
});

it('displays the wallpaper image after event', function () {
    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->assertSeeHtml('src="/storage/wallpapers/test-id.png"');
});

it('shows download button after image generation', function () {
    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->assertDontSee('downloadImage')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->assertSeeHtml('wire:click="downloadImage"');
});

it('shows friendly error toast when download fails', function () {
    Storage::fake('public');

    $wallpaperData = [
        'id' => 'nonexistent.png',
        'url' => '/storage/wallpapers/nonexistent.png',
        'path' => 'wallpapers/nonexistent.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->call('downloadImage')
        ->assertNotDispatched('download');
});

it('streams download and deletes file from storage', function () {
    Storage::fake('public');
    Storage::disk('public')->put('wallpapers/test-id.png', 'fake-image-content');

    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->call('downloadImage')
        ->assertFileDownloaded('phone_wallpaper.png');

    Storage::disk('public')->assertMissing('wallpapers/test-id.png');
});

it('clears wallpaper state after download', function () {
    Storage::fake('public');
    Storage::disk('public')->put('wallpapers/test-id.png', 'fake-image-content');

    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->call('downloadImage')
        ->assertSet('wallpapers.mobile', null);
});

it('streams download with desktop filename when device type is desktop', function () {
    Storage::fake('public');
    Storage::disk('public')->put('wallpapers/test-id.png', 'fake-image-content');

    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->call('setDeviceType', 'desktop')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->call('downloadImage')
        ->assertFileDownloaded('desktop_wallpaper.png');
});
