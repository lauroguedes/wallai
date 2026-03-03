<?php

use App\Enums\BackgroundStyle;

it('has exactly 18 cases', function () {
    expect(BackgroundStyle::cases())->toHaveCount(18);
});

it('has non-empty title for all cases', function (BackgroundStyle $style) {
    expect($style->title())->toBeString()->not->toBeEmpty();
})->with(BackgroundStyle::cases());

it('has non-empty description for all cases', function (BackgroundStyle $style) {
    expect($style->description())->toBeString()->not->toBeEmpty();
})->with(BackgroundStyle::cases());

it('has a valid image URL for all cases', function (BackgroundStyle $style) {
    expect($style->image())
        ->toBeString()
        ->toStartWith('https://picsum.photos/seed/');
})->with(BackgroundStyle::cases());

it('has non-empty system prompt for all cases', function (BackgroundStyle $style) {
    expect($style->systemPrompt())->toBeString()->not->toBeEmpty();
})->with(BackgroundStyle::cases());

it('round-trips through from() for all cases', function (BackgroundStyle $style) {
    expect(BackgroundStyle::from($style->value))->toBe($style);
})->with(BackgroundStyle::cases());
