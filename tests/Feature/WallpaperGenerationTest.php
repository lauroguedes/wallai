<?php

use App\Ai\Agents\PromptGenerator;
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

it('throws ServiceGeneratorException when image generation fails', function () {
    Image::fake([
        fn () => throw new \RuntimeException('API error'),
    ]);

    $service = app(WallpaperService::class);
    $service->generateImage('test', 'realistic');
})->throws(ServiceGeneratorException::class);

it('throws ServiceGeneratorException when prompt generation fails', function () {
    PromptGenerator::fake([
        fn () => throw new \RuntimeException('API error'),
    ]);

    $service = app(WallpaperService::class);
    $service->generatePrompt('realistic');
})->throws(ServiceGeneratorException::class);

it('builds image prompt combining user prompt with style template', function () {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'buildImagePrompt');

    $result = $reflection->invoke($service, 'a serene mountain landscape', 'realistic');

    expect($result)
        ->toContain('Realistic')
        ->toContain('a serene mountain landscape')
        ->toContain('realistic');
});

it('builds image prompt with style-specific keywords', function (string $style, string $expectedKeyword) {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'buildImagePrompt');

    $result = $reflection->invoke($service, 'test prompt', $style);

    expect($result)->toContain($expectedKeyword);
})->with([
    ['realistic', 'realistic'],
    ['artistic', 'brushwork'],
    ['abstract', 'geometric'],
]);
