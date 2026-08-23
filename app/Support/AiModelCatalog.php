<?php

namespace App\Support;

use App\Enums\GenerationProvider;

class AiModelCatalog
{
    public const TEXT = GenerationProvider::TEXT;

    public const IMAGE = GenerationProvider::IMAGE;

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function options(GenerationProvider $provider, string $capability): array
    {
        return $provider->modelOptions($capability);
    }

    /**
     * @return list<string>
     */
    public function ids(GenerationProvider $provider, string $capability): array
    {
        return array_values(array_column($this->options($provider, $capability), 'id'));
    }

    public function default(GenerationProvider $provider, string $capability): string
    {
        return $provider->defaultModel($capability) ?? '';
    }

    public function resolve(GenerationProvider $provider, string $capability, ?string $model): string
    {
        if ($this->allowsCustomModel($provider, $capability) && filled($model)) {
            return trim($model);
        }

        return in_array($model, $this->ids($provider, $capability), true)
            ? $model
            : $this->default($provider, $capability);
    }

    public function allowsCustomModel(GenerationProvider $provider, string $capability): bool
    {
        return $provider->allowsCustomModel($capability);
    }
}
