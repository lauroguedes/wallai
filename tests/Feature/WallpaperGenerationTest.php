<?php

use App\Ai\Agents\PromptGenerator;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function () {
    Storage::fake('public');
});

it('generates an image and stores it to disk', function () {
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generateImage('a beautiful sunset', 'realistic');

    expect($result)
        ->toHaveKeys(['id', 'url', 'path', 'extension'])
        ->and($result['path'])->toStartWith('wallpapers/')
        ->and($result['extension'])->toBeString();

    Storage::disk('public')->assertExists($result['path']);

    Image::assertGenerated(fn ($prompt) => $prompt->contains('sunset'));
});

it('generates a landscape image for desktop device type', function () {
    Image::fake([
        base64_encode('fake-desktop-image'),
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generateImage('a mountain range', 'realistic', DeviceType::Desktop);

    expect($result)
        ->toHaveKeys(['id', 'url', 'path', 'extension']);

    Storage::disk('public')->assertExists($result['path']);

    Image::assertGenerated(fn ($prompt) => $prompt->contains('mountain') && $prompt->contains('desktop'));
});

it('generates a creative prompt using the agent', function () {
    PromptGenerator::fake([
        'A breathtaking aurora borealis over a snow-capped mountain range',
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generatePrompt('realistic');

    expect($result)
        ->toBeString()
        ->not->toBeEmpty();

    PromptGenerator::assertPrompted(fn ($prompt) => $prompt->contains('realistic'));
});

it('generates a prompt with desktop context', function () {
    PromptGenerator::fake([
        'A sweeping panoramic cityscape at golden hour',
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generatePrompt('artistic', DeviceType::Desktop);

    expect($result)
        ->toBeString()
        ->not->toBeEmpty();

    PromptGenerator::assertPrompted(fn ($prompt) => $prompt->contains('desktop'));
});

it('throws ServiceGeneratorException with image_generation operation on failure', function () {
    Image::fake([
        fn () => throw new \RuntimeException('API error'),
    ]);

    $service = app(WallpaperService::class);

    try {
        $service->generateImage('test', 'realistic');
        test()->fail('Expected ServiceGeneratorException');
    } catch (ServiceGeneratorException $e) {
        expect($e)
            ->getOperation()->toBe('image_generation')
            ->getUserMessage()->toBe('We could not generate your wallpaper. Please try again.')
            ->getPrevious()->toBeInstanceOf(\RuntimeException::class);
    }
});

it('throws ServiceGeneratorException with prompt_generation operation on failure', function () {
    PromptGenerator::fake([
        fn () => throw new \RuntimeException('API error'),
    ]);

    $service = app(WallpaperService::class);

    try {
        $service->generatePrompt('realistic');
        test()->fail('Expected ServiceGeneratorException');
    } catch (ServiceGeneratorException $e) {
        expect($e)
            ->getOperation()->toBe('prompt_generation')
            ->getUserMessage()->toBe('We could not generate a prompt. Please try again.')
            ->getPrevious()->toBeInstanceOf(\RuntimeException::class);
    }
});

it('builds image prompt combining user prompt with style template', function () {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'buildImagePrompt');

    $result = $reflection->invoke($service, 'a serene mountain landscape', 'realistic', DeviceType::Mobile);

    expect($result)
        ->toContain('Realistic')
        ->toContain('a serene mountain landscape')
        ->toContain('realistic')
        ->toContain('Portrait');
});

it('builds image prompt with style-specific keywords', function (string $style, string $expectedKeyword) {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'buildImagePrompt');

    $result = $reflection->invoke($service, 'test prompt', $style, DeviceType::Mobile);

    expect($result)->toContain($expectedKeyword);
})->with([
    ['realistic', 'realistic'],
    ['artistic', 'brushwork'],
    ['abstract', 'geometric'],
]);

it('builds desktop image prompt with landscape orientation', function () {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'buildImagePrompt');

    $result = $reflection->invoke($service, 'a cityscape', 'artistic', DeviceType::Desktop);

    expect($result)
        ->toContain('Landscape')
        ->toContain('desktop')
        ->toContain('a cityscape');
});
