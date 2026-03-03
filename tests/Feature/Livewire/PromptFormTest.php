<?php

use App\Ai\Agents\PromptGenerator;
use App\Enums\DeviceType;
use App\Enums\ImageType;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('renders with default state', function () {
    Livewire::test('prompt-form')
        ->assertSet('prompt', '')
        ->assertSet('selectedOption', ImageType::Realistic->value)
        ->assertSet('deviceType', DeviceType::Mobile->value)
        ->assertSee('Generate');
});

it('updates device type when device-type-changed event is received', function () {
    Livewire::test('prompt-form')
        ->dispatch('device-type-changed', 'desktop')
        ->assertSet('deviceType', 'desktop');
});

it('dispatches wallpaper-generated event on successful generation', function () {
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    Livewire::test('prompt-form')
        ->set('prompt', 'a beautiful mountain landscape')
        ->set('selectedOption', 'realistic')
        ->call('generate')
        ->assertDispatched('wallpaper-generated');
});

it('passes device type to service on generate', function () {
    Image::fake([
        base64_encode('fake-image-content'),
    ]);

    Livewire::test('prompt-form')
        ->set('prompt', 'a panoramic cityscape')
        ->set('selectedOption', 'realistic')
        ->dispatch('device-type-changed', 'desktop')
        ->call('generate')
        ->assertDispatched('wallpaper-generated');

    Image::assertGenerated(fn ($prompt) => $prompt->contains('desktop'));
});

it('updates prompt when generatePrompt is called', function () {
    PromptGenerator::fake([
        'A stunning cosmic nebula with vibrant purple and blue hues',
    ]);

    Livewire::test('prompt-form')
        ->set('selectedOption', 'abstract')
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
    Image::fake([
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
