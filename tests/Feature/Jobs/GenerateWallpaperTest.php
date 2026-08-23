<?php

use App\Ai\Agents\ImagePromptAgent;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Jobs\GenerateWallpaper;
use App\Services\WallpaperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function () {
    Storage::fake('public');
    config([
        'ai.providers.openai.key' => 'test-openai-key',
        'ai.providers.gemini.key' => 'test-gemini-key',
    ]);
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
    $job->handle(app(WallpaperService::class));

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
    $job->handle(app(WallpaperService::class));

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
    $job->handle(app(WallpaperService::class));

    $wallpapers = Cache::get("wallpapers:{$sessionId}:mobile");

    expect($wallpapers)->toHaveCount(2)
        ->and($wallpapers[0]['id'])->toBe('existing.png');
});

it('decrements pending job count on success', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 2);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a galaxy', BackgroundStyle::Surrealism, DeviceType::Desktop);
    $job->handle(app(WallpaperService::class));

    expect((int) Cache::get("pending_jobs:{$sessionId}"))->toBe(1);
});

it('stores failed status and decrements pending count on failure', function () {
    $sessionId = 'test-session-id';
    $jobId = 'test-job-id';
    Cache::put("pending_jobs:{$sessionId}", 1);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a sunset', BackgroundStyle::PhotoRealist, DeviceType::Mobile);
    $job->failed(new RuntimeException('API error'));

    $result = Cache::get("wallpaper_job:{$jobId}");

    expect($result)
        ->not->toBeNull()
        ->and($result['status'])->toBe('failed')
        ->and($result['message'])->toBeString();

    expect((int) Cache::get("pending_jobs:{$sessionId}"))->toBe(0);
});

it('does not generate or restore images for a reset session', function () {
    $sessionId = 'reset-session-id';
    $jobId = 'reset-job-id';

    Cache::put("wallpaper_session:{$sessionId}:reset", true);

    $job = new GenerateWallpaper($sessionId, $jobId, 'a sunset', BackgroundStyle::PhotoRealist, DeviceType::Mobile);
    $job->handle(app(WallpaperService::class));

    ImagePromptAgent::assertNeverPrompted();

    expect(Cache::get("wallpaper_job:{$jobId}"))->toBeNull()
        ->and(Cache::get("wallpapers:{$sessionId}:mobile"))->toBeNull();
});

it('deletes an image that finishes after its session was reset', function () {
    $sessionId = 'reset-during-generation';
    $jobId = 'late-job-id';
    $path = "wallpapers/{$sessionId}/late.png";

    Storage::disk('public')->put($path, 'late-image');
    Cache::put("wallpaper_session:{$sessionId}:reset", true);

    app(WallpaperService::class)->completeGeneration($sessionId, $jobId, DeviceType::Mobile, [
        'id' => 'late.png',
        'url' => "/storage/{$path}",
        'path' => $path,
        'extension' => 'png',
        'style' => BackgroundStyle::PhotoRealist->value,
    ]);

    Storage::disk('public')->assertMissing($path);

    expect(Cache::get("wallpaper_job:{$jobId}"))->toBeNull()
        ->and(Cache::get("wallpapers:{$sessionId}:mobile"))->toBeNull();
});
