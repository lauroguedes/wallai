<?php

use App\Enums\GenerationProvider;
use App\Exceptions\MissingAiCredentialsException;
use App\Models\AiProviderSetting;
use App\Services\RuntimeAiProvider;
use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'ai.providers.openai.key' => null,
        'ai.providers.gemini.key' => null,
    ]);
});

it('registers an isolated provider for one operation and removes it afterward', function () {
    $settings = new AiProviderSetting;
    $settings->openai_api_key = 'runtime-secret-key';

    $alias = app(RuntimeAiProvider::class)->using(
        GenerationProvider::OpenAI,
        $settings,
        function (string $alias): string {
            expect(config("ai.providers.{$alias}.driver"))->toBe(GenerationProvider::OpenAI->value)
                ->and(config("ai.providers.{$alias}.key"))->toBe('runtime-secret-key')
                ->and(Ai::textProvider($alias))->toBeInstanceOf(TextProvider::class);

            return $alias;
        },
    );

    expect(config("ai.providers.{$alias}"))->toBeNull();
});

it('rejects an operation when its provider has no key', function () {
    app(RuntimeAiProvider::class)->using(
        GenerationProvider::Gemini,
        null,
        fn (string $provider) => $provider,
    );
})->throws(MissingAiCredentialsException::class, 'No API key is configured for Google Gemini.');

it('can use the server configured key without saved session settings', function () {
    config(['ai.providers.gemini.key' => 'server-runtime-key']);

    app(RuntimeAiProvider::class)->using(
        GenerationProvider::Gemini,
        null,
        function (string $alias): void {
            expect(config("ai.providers.{$alias}.key"))->toBe('server-runtime-key');
        },
    );
});

it('configures Ollama with a custom URL without requiring an API key', function () {
    $settings = new AiProviderSetting;
    $settings->ollama_url = 'http://host.docker.internal:11434';

    $alias = app(RuntimeAiProvider::class)->using(
        GenerationProvider::Ollama,
        $settings,
        function (string $alias): string {
            expect(config("ai.providers.{$alias}.driver"))->toBe(GenerationProvider::Ollama->value)
                ->and(config("ai.providers.{$alias}.key"))->toBe('')
                ->and(config("ai.providers.{$alias}.url"))->toBe('http://host.docker.internal:11434')
                ->and(Ai::textProvider($alias))->toBeInstanceOf(TextProvider::class);

            return $alias;
        },
    );

    expect(config("ai.providers.{$alias}"))->toBeNull();
});
