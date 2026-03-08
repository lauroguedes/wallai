<?php

use App\Services\WallpaperService;

test('getExtension returns correct extension for known mime types', function (string $mime, string $expected) {
    $service = new WallpaperService;

    $reflection = new ReflectionMethod($service, 'getExtension');

    $result = $reflection->invoke($service, $mime);

    expect($result)->toBe($expected);
})->with([
    ['image/png', 'png'],
    ['image/jpeg', 'jpg'],
    ['image/jpg', 'jpg'],
    ['image/webp', 'webp'],
    ['image/unknown', 'png'],
]);

test('getExtension returns png for null mime', function () {
    $service = new WallpaperService;

    $reflection = new ReflectionMethod($service, 'getExtension');

    $result = $reflection->invoke($service, null);

    expect($result)->toBe('png');
});
