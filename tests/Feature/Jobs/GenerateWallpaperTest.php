<?php

use App\Ai\Agents\ImagePromptAgent;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Jobs\GenerateWallpaper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function () {
    Storage::fake('public');
    ImagePromptAgent::fake();
    Image::fake([
        base64_encode('fake-image-content'),
    ]);
});

it('stores completed result in cache on success', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 1);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a sunset', BackgroundStyle::PhotoRealist, DeviceType::Mobile);
    $job->handle(app(\App\Services\WallpaperService::class));

    $result = Cache::get("wallpaper_job:{$jobId}");

    expect($result)
        ->not->toBeNull()
        ->and($result['status'])->toBe('completed')
        ->and($result['wallpaper'])->toHaveKeys(['id', 'url', 'path', 'extension']);
});

it('stores wallpaper under session directory', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 1);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a mountain', BackgroundStyle::NaturalLandscape, DeviceType::Mobile);
    $job->handle(app(\App\Services\WallpaperService::class));

    $result = Cache::get("wallpaper_job:{$jobId}");
    $path = $result['wallpaper']['path'];

    expect($path)->toStartWith("wallpapers/{$sessionId}/");
    Storage::disk('public')->assertExists($path);
});

it('appends wallpaper to session registry with device type', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 1);
    Cache::put("wallpapers:{$sessionId}:mobile", [
        ['id' => 'existing.png', 'url' => '/existing.png', 'path' => 'wallpapers/existing.png', 'extension' => 'png'],
    ]);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a nebula', BackgroundStyle::AbstractFluidArt, DeviceType::Mobile);
    $job->handle(app(\App\Services\WallpaperService::class));

    $wallpapers = Cache::get("wallpapers:{$sessionId}:mobile");

    expect($wallpapers)->toHaveCount(2)
        ->and($wallpapers[0]['id'])->toBe('existing.png');
});

it('decrements pending job count on success', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 2);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a galaxy', BackgroundStyle::Surrealism, DeviceType::Desktop);
    $job->handle(app(\App\Services\WallpaperService::class));

    expect((int) Cache::get("pending_jobs:{$sessionId}"))->toBe(1);
});

it('stores failed status and decrements pending count on failure', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 1);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a sunset', BackgroundStyle::PhotoRealist, DeviceType::Mobile);
    $job->failed(new \RuntimeException('API error'));

    $result = Cache::get("wallpaper_job:{$jobId}");

    expect($result)
        ->not->toBeNull()
        ->and($result['status'])->toBe('failed')
        ->and($result['message'])->toBeString();

    expect((int) Cache::get("pending_jobs:{$sessionId}"))->toBe(0);
});
