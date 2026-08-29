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
        ->toContain('FROM node:26.8.1-bookworm-slim@sha256:367679cf9792759492a486e4aa4b421764d71a9546a6dae8aab81a99eb797b3e AS frontend')
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
