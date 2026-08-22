<?php

namespace App\Services;

use App\Enums\GenerationProvider;
use App\Exceptions\MissingAiCredentialsException;
use App\Models\AiProviderSetting;
use App\Support\AiModelCatalog;

class AiProviderSettings
{
    private const SESSION_KEY = 'ai_provider_settings_id';

    public function __construct(private AiModelCatalog $models) {}

    public function current(): ?AiProviderSetting
    {
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
    ): AiProviderSetting {
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

        $settings->save();

        session()->put(self::SESSION_KEY, $settings->getKey());

        return $settings;
    }

    public function forget(): void
    {
        $this->current()?->delete();
        session()->forget(self::SESSION_KEY);
    }

    public function removeKey(GenerationProvider $provider): ?AiProviderSetting
    {
        $settings = $this->current();

        if ($settings === null) {
            return null;
        }

        $settings->setAttribute($provider->apiKeyAttribute(), null);
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
        return $settings?->image_provider
            ?? GenerationProvider::from((string) config('wallpaper.ai.image_provider', GenerationProvider::Gemini->value));
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

    public function hasStoredKey(GenerationProvider $provider, ?AiProviderSetting $settings = null): bool
    {
        return $settings?->apiKeyFor($provider) !== null;
    }

    public function keyStatus(GenerationProvider $provider, ?AiProviderSetting $settings = null): string
    {
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

            if ($this->effectiveKey($provider, $settings) === null) {
                throw new MissingAiCredentialsException($provider);
            }
        }
    }
}
