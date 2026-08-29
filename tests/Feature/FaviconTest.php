<?php

use Illuminate\Support\Facades\File;

it('publishes the complete WallAI favicon set', function () {
    $faviconFiles = [
        'favicon.ico',
        'favicon-16x16.png',
        'favicon-32x32.png',
        'favicon-48x48.png',
        'favicon-64x64.png',
        'apple-touch-icon.png',
        'android-chrome-192x192.png',
        'android-chrome-512x512.png',
        'site.webmanifest',
    ];

    foreach ($faviconFiles as $faviconFile) {
        expect(File::exists(public_path($faviconFile)))->toBeTrue($faviconFile.' is missing.');
    }
});

it('configures favicon metadata in the application layout', function () {
    $layout = File::get(resource_path('views/layouts/app.blade.php'));

    expect($layout)
        ->toContain('rel="icon" href="/favicon.ico" sizes="any"')
        ->toContain('rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png"')
        ->toContain('rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png"')
        ->toContain('rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png"')
        ->toContain('rel="manifest" href="/site.webmanifest"')
        ->toContain('media="(prefers-color-scheme: light)"')
        ->toContain('media="(prefers-color-scheme: dark)"');
});

it('uses WallAI metadata in the web app manifest', function () {
    $manifest = json_decode(File::get(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->toHaveKey('name', 'WallAI')
        ->toHaveKey('short_name', 'WallAI')
        ->toHaveKey('start_url', '/')
        ->toHaveKey('scope', '/')
        ->toHaveKey('display', 'standalone')
        ->and($manifest['icons'])->toHaveCount(2);
});
