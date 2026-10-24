<?php

use Illuminate\Support\Facades\File;

it('builds frontend assets with the current stable Node release and UI sources', function () {
    $dockerfile = File::get(base_path('Dockerfile'));
    $frontendBuildPosition = strpos($dockerfile, 'RUN npm run build');
    $marySourcePosition = strpos(
        $dockerfile,
        'COPY --from=composer-dependencies /app/vendor/robsontenorio/mary/src/View/Components ./vendor/robsontenorio/mary/src/View/Components',
    );

    expect($dockerfile)
        ->toContain('FROM node:26.12.0-bookworm-slim@sha256:742a0fe7320e09e0321222b68165e6ac731d30470e7ea7f98f9bfc3a6e1034fe AS frontend')
        ->and($marySourcePosition)->not->toBeFalse()
        ->and($frontendBuildPosition)->not->toBeFalse()
        ->and($marySourcePosition)->toBeLessThan($frontendBuildPosition);
});

it('compiles the configured light and dark themes', function () {
    $css = File::get(resource_path('css/app.css'));

    expect($css)
        ->toContain('themes: light --default, dark --prefersdark;')
        ->toContain('@custom-variant dark (&:where(.dark, .dark *));')
        ->toContain('.dark .left-side-bg')
        ->toContain('.light .device-toggle-button.btn-active');
});

it('enforces production deployment checks only in production containers', function () {
    $initializer = File::get(base_path('docker/initialize.sh'));

    expect($initializer)
        ->toContain('if [ "${APP_ENV:-production}" = "production" ]; then')
        ->toContain('php artisan wallai:doctor --deployment')
        ->toContain('php artisan wallai:doctor');
});
