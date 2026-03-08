<?php

use App\Ai\Agents\ImagePromptAgent;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;

it('can be instantiated with style and device type', function () {
    $agent = new ImagePromptAgent(BackgroundStyle::PhotoRealist, DeviceType::Mobile);

    expect($agent->style)->toBe(BackgroundStyle::PhotoRealist)
        ->and($agent->deviceType)->toBe(DeviceType::Mobile);
});

it('includes style title in instructions', function () {
    $agent = new ImagePromptAgent(BackgroundStyle::PixelArt, DeviceType::Desktop);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain('Pixel Art')
        ->toContain('pixel art');
});

it('includes device orientation in instructions', function () {
    $agent = new ImagePromptAgent(BackgroundStyle::NaturalLandscape, DeviceType::Mobile);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain('portrait')
        ->toContain('mobile')
        ->toContain('9:16')
        ->toContain('2160x3840');
});

it('includes desktop context in instructions', function () {
    $agent = new ImagePromptAgent(BackgroundStyle::CyberpunkCityscape, DeviceType::Desktop);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain('landscape')
        ->toContain('desktop')
        ->toContain('16:9')
        ->toContain('3840x2160');
});

it('includes style system prompt in instructions', function () {
    $agent = new ImagePromptAgent(BackgroundStyle::Surrealism, DeviceType::Mobile);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain(BackgroundStyle::Surrealism->systemPrompt());
});

it('includes negative prompt rules in instructions', function () {
    $agent = new ImagePromptAgent(BackgroundStyle::PhotoRealist, DeviceType::Mobile);

    $instructions = $agent->instructions();

    expect((string) $instructions)
        ->toContain('negative_prompt')
        ->toContain('watermark')
        ->toContain('ui elements');
});
