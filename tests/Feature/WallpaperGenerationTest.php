<?php

use App\Ai\Agents\ImagePromptAgent;
use App\Ai\Agents\PromptGenerator;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

beforeEach(function () {
    Storage::fake('public');
});

it('generates an image and stores it to disk', function () {
    ImagePromptAgent::fake();
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generateImage('a beautiful sunset', BackgroundStyle::PhotoRealist);

    expect($result)
        ->toHaveKeys(['id', 'url', 'path', 'extension'])
        ->and($result['path'])->toStartWith('wallpapers/')
        ->and($result['extension'])->toBeString();

    Storage::disk('public')->assertExists($result['path']);

    ImagePromptAgent::assertPrompted(fn ($prompt) => $prompt->contains('sunset'));
});

it('generates a landscape image for desktop device type', function () {
    ImagePromptAgent::fake();
    Image::fake([
        base64_encode('fake-desktop-image'),
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generateImage('a mountain range', BackgroundStyle::NaturalLandscape, DeviceType::Desktop);

    expect($result)
        ->toHaveKeys(['id', 'url', 'path', 'extension']);

    Storage::disk('public')->assertExists($result['path']);

    ImagePromptAgent::assertPrompted(fn ($prompt) => $prompt->contains('mountain'));
});

it('uses ImagePromptAgent to engineer the image prompt', function () {
    ImagePromptAgent::fake();
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    $service = app(WallpaperService::class);
    $service->generateImage('a cosmic nebula', BackgroundStyle::AbstractFluidArt);

    ImagePromptAgent::assertPrompted(fn ($prompt) => $prompt->contains('cosmic nebula'));
    Image::assertGenerated(fn ($prompt) => strlen($prompt->prompt) > 0);
});

it('generates a creative prompt using the agent', function () {
    PromptGenerator::fake([
        'A breathtaking aurora borealis over a snow-capped mountain range',
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generatePrompt(BackgroundStyle::NaturalLandscape);

    expect($result)
        ->toBeString()
        ->not->toBeEmpty();

    PromptGenerator::assertPrompted(fn ($prompt) => $prompt->contains('Natural Landscape'));
});

it('generates a prompt with desktop context', function () {
    PromptGenerator::fake([
        'A sweeping panoramic cityscape at golden hour',
    ]);

    $service = app(WallpaperService::class);
    $result = $service->generatePrompt(BackgroundStyle::StylizedIllustration, DeviceType::Desktop);

    expect($result)
        ->toBeString()
        ->not->toBeEmpty();

    PromptGenerator::assertPrompted(fn ($prompt) => $prompt->contains('desktop'));
});

it('throws ServiceGeneratorException with image_generation operation on failure', function () {
    ImagePromptAgent::fake([
        fn () => throw new \RuntimeException('API error'),
    ]);

    $service = app(WallpaperService::class);

    try {
        $service->generateImage('test', BackgroundStyle::PhotoRealist);
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
        $service->generatePrompt(BackgroundStyle::PixelArt);
        test()->fail('Expected ServiceGeneratorException');
    } catch (ServiceGeneratorException $e) {
        expect($e)
            ->getOperation()->toBe('prompt_generation')
            ->getUserMessage()->toBe('We could not generate a prompt. Please try again.')
            ->getPrevious()->toBeInstanceOf(\RuntimeException::class);
    }
});

it('flattens structured prompt into a descriptive string', function () {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'flattenStructuredPrompt');

    $structured = [
        'meta' => [
            'model_version' => 'v1',
            'task' => 'wallpaper_generation',
            'thinking_level' => 'high',
            'consistency_id' => 'test-123',
            'seed' => 42,
        ],
        'global_settings' => [
            'aspect_ratio' => '9:16',
            'resolution' => '2160x3840',
            'guidance_scale' => 12.0,
            'quality_mode' => 'high',
        ],
        'subject' => [
            'entity_type' => 'landscape',
            'description' => ['vast mountain range', 'snow-capped peaks', 'golden hour lighting'],
            'materials' => ['skin' => 'none', 'clothing' => 'none'],
            'arrangement' => 'panoramic wide composition',
        ],
        'scene' => [
            'environment' => 'alpine mountain valley',
            'lighting' => [
                'source' => 'natural sunlight',
                'direction' => 'side',
                'atmosphere' => 'warm golden',
            ],
            'objects' => ['pine trees', 'flowing river', 'wildflowers'],
        ],
        'technical_camera' => [
            'lens' => 'wide-angle 24mm',
            'aperture' => 'f/8',
            'iso' => 100,
            'camera_angle' => 'eye-level',
        ],
        'text_rendering' => [
            'content' => '',
            'font_style' => 'none',
            'placement' => 'none',
        ],
        'negative_prompt' => ['text', 'watermark', 'ui elements', 'blurry'],
    ];

    $result = $reflection->invoke($service, $structured);

    expect($result)
        ->toContain('Landscape:')
        ->toContain('vast mountain range')
        ->toContain('snow-capped peaks')
        ->toContain('panoramic wide composition')
        ->toContain('alpine mountain valley')
        ->toContain('natural sunlight')
        ->toContain('wide-angle 24mm')
        ->toContain('f/8')
        ->toContain('High quality')
        ->toContain('2160x3840')
        ->toContain('Avoid:')
        ->toContain('watermark')
        ->not->toContain('Text:');
});

it('includes text rendering in flattened prompt when content is provided', function () {
    $service = app(WallpaperService::class);

    $reflection = new ReflectionMethod($service, 'flattenStructuredPrompt');

    $structured = [
        'subject' => [
            'entity_type' => 'typography',
            'description' => ['bold lettering'],
            'arrangement' => 'centered',
        ],
        'scene' => [
            'environment' => 'abstract background',
            'lighting' => ['source' => 'ambient', 'direction' => 'even', 'atmosphere' => 'neutral'],
            'objects' => [],
        ],
        'technical_camera' => [
            'lens' => '50mm',
            'aperture' => 'f/4',
            'iso' => 200,
            'camera_angle' => 'eye-level',
        ],
        'global_settings' => [
            'quality_mode' => 'ultra',
            'resolution' => '3840x2160',
        ],
        'text_rendering' => [
            'content' => 'HELLO WORLD',
            'font_style' => 'bold geometric sans-serif',
            'placement' => 'center_bottom',
        ],
        'negative_prompt' => ['blurry'],
    ];

    $result = $reflection->invoke($service, $structured);

    expect($result)
        ->toContain('Text: "HELLO WORLD"')
        ->toContain('bold geometric sans-serif')
        ->toContain('center_bottom');
});
