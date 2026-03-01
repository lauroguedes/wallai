<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders with empty state showing logo', function () {
    Livewire::test('preview')
        ->assertSet('wallpaper', null)
        ->assertSeeLivewire('logo');
});

it('updates wallpaper when wallpaper-generated event is received', function () {
    $wallpaperData = [
        'id' => 'test-id.png',
        'url' => '/storage/wallpapers/test-id.png',
        'path' => 'wallpapers/test-id.png',
        'extension' => 'png',
    ];

    Livewire::test('preview')
        ->dispatch('wallpaper-generated', $wallpaperData)
        ->assertSet('wallpaper', $wallpaperData);
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

it('streams download from storage', function () {
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
});
