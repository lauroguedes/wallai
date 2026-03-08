<?php

use App\Enums\DeviceType;

it('has correct string values', function () {
    expect(DeviceType::Mobile->value)->toBe('mobile')
        ->and(DeviceType::Desktop->value)->toBe('desktop');
});

it('can be constructed from string values', function (string $value, DeviceType $expected) {
    expect(DeviceType::from($value))->toBe($expected);
})->with([
    ['mobile', DeviceType::Mobile],
    ['desktop', DeviceType::Desktop],
]);

it('returns correct orientation', function (DeviceType $type, string $orientation) {
    expect($type->orientation())->toBe($orientation);
})->with([
    [DeviceType::Mobile, 'portrait'],
    [DeviceType::Desktop, 'landscape'],
]);

it('returns non-empty prompt context', function (DeviceType $type) {
    expect($type->promptContext())
        ->toBeString()
        ->not->toBeEmpty();
})->with([
    DeviceType::Mobile,
    DeviceType::Desktop,
]);
