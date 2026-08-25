<?php

namespace App\Services;

use App\Enums\GenerationProvider;
use App\Exceptions\MissingAiCredentialsException;
use App\Models\AiProviderSetting;
use App\Support\AiModelCatalog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AiProviderSettings
{
    private const SESSION_KEY = 'ai_provider_settings_id';

    public function __construct(
        private AiModelCatalog $models,
        private ApplicationSetup $setup,
    ) {}

    public function current(): ?AiProviderSetting
    {
        if ($this->setup->authenticationEnabled()) {
            return AiProviderSetting::query()
                ->where('user_id', $this->authenticatedUserId())
                ->first();
        }

        $id = session()->get(self::SESSION_KEY);

        return is_string($id) ? AiProviderSetting::query()->find($id) : null;
    }

    public function find(?string $id): ?AiProviderSetting
    {
        return $id === null ? null : AiProviderSetting::query()->find($id);
    }

    public function save(
        GenerationProvider $textProvider,
        string $textModel,
        GenerationProvider $imageProvider,
        string $imageModel,
        ?string $openAiApiKey = null,
        ?string $geminiApiKey = null,
        ?string $ollamaUrl = null,
    ): AiProviderSetting {
        if (! $textProvider->supports(GenerationProvider::TEXT)) {
            throw new InvalidArgumentException("{$textProvider->label()} does not support text generation.");
        }

        if (! $imageProvider->supports(GenerationProvider::IMAGE)) {
            throw new InvalidArgumentException("{$imageProvider->label()} does not support image generation.");
        }

        if ($textProvider === GenerationProvider::Ollama
            && filled($ollamaUrl)
            && ! $this->isOllamaHostAllowed((string) $ollamaUrl)) {
            throw new InvalidArgumentException('The Ollama server host is not allowed by this installation.');
        }

        $settings = $this->current() ?? new AiProviderSetting;

        $settings->text_provider = $textProvider;
        $settings->text_model = $this->models->resolve($textProvider, AiModelCatalog::TEXT, $textModel);
        $settings->image_provider = $imageProvider;
        $settings->image_model = $this->models->resolve($imageProvider, AiModelCatalog::IMAGE, $imageModel);

        if (filled($openAiApiKey)) {
            $settings->openai_api_key = trim($openAiApiKey);
        }

        if (filled($geminiApiKey)) {
            $settings->gemini_api_key = trim($geminiApiKey);
        }

        if (filled($ollamaUrl)) {
            $settings->ollama_url = rtrim(trim($ollamaUrl), '/');
        }

        if ($this->setup->authenticationEnabled()) {
            $settings->user_id = $this->authenticatedUserId();
        }

        $settings->save();

        if (! $this->setup->authenticationEnabled()) {
            session()->put(self::SESSION_KEY, $settings->getKey());
        }

        return $settings;
    }

    public function forget(): void
    {
        $this->current()?->delete();
        session()->forget(self::SESSION_KEY);
    }

    private function authenticatedUserId(): int
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new AuthenticationException;
        }

        return (int) $userId;
    }

    public function removeKey(GenerationProvider $provider): ?AiProviderSetting
    {
        $settings = $this->current();

        if ($settings === null) {
            return null;
        }

        $attribute = $provider->apiKeyAttribute();

        if ($attribute === null) {
            return $settings;
        }

        $settings->setAttribute($attribute, null);
        $settings->save();

        return $settings;
    }

    public function textProvider(?AiProviderSetting $settings = null): GenerationProvider
    {
        return $settings?->text_provider
            ?? GenerationProvider::from((string) config('wallpaper.ai.text_provider', GenerationProvider::Gemini->value));
    }

    public function imageProvider(?AiProviderSetting $settings = null): GenerationProvider
    {
        $provider = $settings?->image_provider
            ?? GenerationProvider::from((string) config('wallpaper.ai.image_provider', GenerationProvider::Gemini->value));

        return $provider->supports(GenerationProvider::IMAGE)
            ? $provider
            : GenerationProvider::Gemini;
    }

    public function textModel(?AiProviderSetting $settings = null, ?GenerationProvider $provider = null): string
    {
        $provider ??= $this->textProvider($settings);

        return $this->models->resolve($provider, AiModelCatalog::TEXT, $settings?->text_model);
    }

    public function imageModel(?AiProviderSetting $settings = null, ?GenerationProvider $provider = null): string
    {
        $provider ??= $this->imageProvider($settings);

        return $this->models->resolve($provider, AiModelCatalog::IMAGE, $settings?->image_model);
    }

    public function effectiveKey(GenerationProvider $provider, ?AiProviderSetting $settings = null): ?string
    {
        $key = $settings?->apiKeyFor($provider)
            ?? config("ai.providers.{$provider->value}.key");

        return filled($key) ? (string) $key : null;
    }

    public function ollamaUrl(?AiProviderSetting $settings = null): string
    {
        $url = $settings?->ollama_url
            ?? config('ai.providers.ollama.url', 'http://localhost:11434');

        $url = rtrim((string) $url, '/');

        if (! $this->isOllamaHostAllowed($url)) {
            throw new InvalidArgumentException('The Ollama server host is not allowed by this installation.');
        }

        return $url;
    }

    public function isOllamaHostAllowed(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        $normalizedHost = Str::lower(trim($host, '[]'));
        $allowedHosts = collect(config('ai.ollama_allowed_hosts', []))
            ->map(fn (mixed $allowedHost): string => Str::lower(trim((string) $allowedHost, '[]')));

        return $allowedHosts->containsStrict($normalizedHost);
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeConfiguration(GenerationProvider $provider, ?AiProviderSetting $settings = null): array
    {
        $configuration = (array) config("ai.providers.{$provider->value}", []);
        $configuration['driver'] = $provider->value;
        $configuration['key'] = $this->effectiveKey($provider, $settings) ?? '';

        if ($provider === GenerationProvider::Ollama) {
            $configuration['url'] = $this->ollamaUrl($settings);
        }

        return $configuration;
    }

    public function hasStoredKey(GenerationProvider $provider, ?AiProviderSetting $settings = null): bool
    {
        return $settings?->apiKeyFor($provider) !== null;
    }

    public function keyStatus(GenerationProvider $provider, ?AiProviderSetting $settings = null): string
    {
        if (! $provider->requiresApiKey()) {
            return 'No API key required';
        }

        $storedKey = $settings?->apiKeyFor($provider);

        if ($storedKey !== null) {
            return 'Saved key ending in '.mb_substr($storedKey, -4);
        }

        return filled(config("ai.providers.{$provider->value}.key"))
            ? 'Server default key available'
            : 'No key configured';
    }

    /**
     * @param  iterable<GenerationProvider>  $providers
     */
    public function ensureConfigured(iterable $providers, ?AiProviderSetting $settings = null): void
    {
        $checked = [];

        foreach ($providers as $provider) {
            if (isset($checked[$provider->value])) {
                continue;
            }

            $checked[$provider->value] = true;

            if ($provider->requiresApiKey() && $this->effectiveKey($provider, $settings) === null) {
                throw new MissingAiCredentialsException($provider);
            }
        }
    }
}
