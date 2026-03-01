<?php

use App\Ai\Agents\PromptGenerator;
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
        ->assertSee('Generate');
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

it('updates prompt when generatePrompt is called', function () {
    PromptGenerator::fake([
        'A stunning cosmic nebula with vibrant purple and blue hues',
    ]);

    Livewire::test('prompt-form')
        ->set('selectedOption', 'abstract')
        ->call('generatePrompt')
        ->assertSet('prompt', 'A stunning cosmic nebula with vibrant purple and blue hues');
});

it('shows error toast when image generation fails', function () {
    Image::fake([
        fn () => throw new \RuntimeException('Generation failed'),
    ]);

    Livewire::test('prompt-form')
        ->set('prompt', 'test prompt')
        ->call('generate')
        ->assertNotDispatched('wallpaper-generated');
});

it('shows error toast when prompt generation fails', function () {
    PromptGenerator::fake([
        fn () => throw new \RuntimeException('Prompt generation failed'),
    ]);

    Livewire::test('prompt-form')
        ->call('generatePrompt')
        ->assertSet('prompt', '');
});
