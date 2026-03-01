<?php

use App\Enums\ImageType;

test('each enum case has the correct string value', function () {
    expect(ImageType::Artistic->value)->toBe('artistic');
    expect(ImageType::Realistic->value)->toBe('realistic');
    expect(ImageType::Abstract->value)->toBe('abstract');
});

test('each enum case can be constructed from its string value', function () {
    expect(ImageType::from('artistic'))->toBe(ImageType::Artistic);
    expect(ImageType::from('realistic'))->toBe(ImageType::Realistic);
    expect(ImageType::from('abstract'))->toBe(ImageType::Abstract);
});

test('each enum case returns a non-empty prompt', function () {
    foreach (ImageType::cases() as $case) {
        expect($case->prompt())->toBeString()->not->toBeEmpty();
    }
});

test('realistic prompt contains photographic keywords', function () {
    expect(ImageType::Realistic->prompt())->toContain('realistic');
});

test('artistic prompt contains painterly keywords', function () {
    expect(ImageType::Artistic->prompt())->toContain('brushwork');
});

test('abstract prompt contains pattern keywords', function () {
    expect(ImageType::Abstract->prompt())->toContain('geometric');
});
