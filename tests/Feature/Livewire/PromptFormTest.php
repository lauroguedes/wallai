<?php

use App\Ai\Agents\ImagePromptAgent;
use App\Ai\Agents\PromptGenerator;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
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

it('updates device type when device-type-changed event is received', function () {
    Livewire::test('prompt-form')
        ->dispatch('device-type-changed', 'desktop')
        ->assertSet('deviceType', 'desktop');
});

it('selects a style and closes the drawer', function () {
    Livewire::test('prompt-form')
        ->set('showDrawer', true)
        ->call('selectStyle', BackgroundStyle::PixelArt->value)
        ->assertSet('selectedStyle', BackgroundStyle::PixelArt->value)
        ->assertSet('showDrawer', false);
});

it('dispatches wallpaper-generated event on successful generation', function () {
    ImagePromptAgent::fake();
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    Livewire::test('prompt-form')
        ->set('prompt', 'a beautiful mountain landscape')
        ->set('selectedStyle', BackgroundStyle::NaturalLandscape->value)
        ->call('generate')
        ->assertDispatched('wallpaper-generated');

    ImagePromptAgent::assertPrompted(fn ($prompt) => $prompt->contains('mountain landscape'));
});

it('passes device type to service on generate', function () {
    ImagePromptAgent::fake();
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    Livewire::test('prompt-form')
        ->set('prompt', 'a panoramic cityscape')
        ->set('selectedStyle', BackgroundStyle::PhotoRealist->value)
        ->dispatch('device-type-changed', 'desktop')
        ->call('generate')
        ->assertDispatched('wallpaper-generated');

    ImagePromptAgent::assertPrompted(fn ($prompt) => $prompt->contains('panoramic cityscape'));
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
        ->dispatch('device-type-changed', 'desktop')
        ->call('generatePrompt');

    PromptGenerator::assertPrompted(fn ($prompt) => $prompt->contains('desktop'));
});

it('shows friendly error toast when image generation fails', function () {
    ImagePromptAgent::fake([
        fn () => throw new \RuntimeException('Generation failed'),
    ]);

    Livewire::test('prompt-form')
        ->set('prompt', 'test prompt')
        ->call('generate')
        ->assertNotDispatched('wallpaper-generated');
});

it('shows friendly error toast when prompt generation fails', function () {
    PromptGenerator::fake([
        fn () => throw new \RuntimeException('Prompt generation failed'),
    ]);

    Livewire::test('prompt-form')
        ->call('generatePrompt')
        ->assertSet('prompt', '');
});
