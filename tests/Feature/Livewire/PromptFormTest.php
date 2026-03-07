<?php

use App\Ai\Agents\PromptGenerator;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Jobs\GenerateWallpaper;
use App\Services\WallpaperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('renders with default state', function () {
    Livewire::test('prompt-form')
        ->assertSet('prompt', '')
        ->assertSet('selectedStyle', BackgroundStyle::NaturalLandscape->value)
        ->assertSet('deviceType', DeviceType::Mobile->value)
        ->assertSet('showDrawer', false)
        ->assertSee('Generate');
});

it('selects a style and closes the drawer', function () {
    Livewire::test('prompt-form')
        ->set('showDrawer', true)
        ->call('selectStyle', BackgroundStyle::PixelArt->value)
        ->assertSet('selectedStyle', BackgroundStyle::PixelArt->value)
        ->assertSet('showDrawer', false);
});

it('dispatches a job and emits wallpaper-job-dispatched event with deviceType on generate', function () {
    Queue::fake();

    Livewire::test('prompt-form')
        ->set('prompt', 'a beautiful mountain landscape')
        ->set('selectedStyle', BackgroundStyle::NaturalLandscape->value)
        ->call('generate')
        ->assertDispatched('wallpaper-job-dispatched', fn ($name, $params) => $params['deviceType'] === 'mobile');

    Queue::assertPushed(GenerateWallpaper::class, function ($job) {
        return $job->prompt === 'a beautiful mountain landscape'
            && $job->style === BackgroundStyle::NaturalLandscape
            && $job->deviceType === DeviceType::Mobile;
    });
});

it('passes device type to dispatched job', function () {
    Queue::fake();

    Livewire::test('prompt-form')
        ->set('prompt', 'a panoramic cityscape')
        ->set('selectedStyle', BackgroundStyle::PhotoRealist->value)
        ->set('deviceType', 'desktop')
        ->call('generate')
        ->assertDispatched('wallpaper-job-dispatched', fn ($name, $params) => $params['deviceType'] === 'desktop');

    Queue::assertPushed(GenerateWallpaper::class, function ($job) {
        return $job->prompt === 'a panoramic cityscape'
            && $job->deviceType === DeviceType::Desktop;
    });
});

it('blocks generation when max pending jobs reached', function () {
    Queue::fake();
    Cache::put('pending_jobs:'.session()->getId(), WallpaperService::maxPendingJobs());

    Livewire::test('prompt-form')
        ->set('prompt', 'test prompt')
        ->call('generate')
        ->assertNotDispatched('wallpaper-job-dispatched');

    Queue::assertNothingPushed();
});

it('updates prompt when generatePrompt is called', function () {
    PromptGenerator::fake([
        'A stunning cosmic nebula with vibrant purple and blue hues',
    ]);

    Livewire::test('prompt-form')
        ->set('selectedStyle', BackgroundStyle::AbstractFluidArt->value)
        ->call('generatePrompt')
        ->assertSet('prompt', 'A stunning cosmic nebula with vibrant purple and blue hues');
});

it('passes device type to service on generatePrompt', function () {
    PromptGenerator::fake([
        'A sweeping panoramic mountain vista',
    ]);

    Livewire::test('prompt-form')
        ->set('deviceType', 'desktop')
        ->call('generatePrompt');

    PromptGenerator::assertPrompted(fn ($prompt) => $prompt->contains('desktop'));
});

it('shows friendly error toast when prompt generation fails', function () {
    PromptGenerator::fake([
        fn () => throw new \RuntimeException('Prompt generation failed'),
    ]);

    Livewire::test('prompt-form')
        ->call('generatePrompt')
        ->assertSet('prompt', '');
});
