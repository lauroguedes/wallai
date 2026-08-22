<?php

namespace App\Support;

use App\Enums\GenerationProvider;
use Illuminate\Support\Arr;

class AiModelCatalog
{
    public const TEXT = 'text';

    public const IMAGE = 'image';

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function options(GenerationProvider $provider, string $capability): array
    {
        return Arr::get(
            config('wallpaper.ai.models', []),
            "{$provider->value}.{$capability}.options",
            [],
        );
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
        return (string) Arr::get(
            config('wallpaper.ai.models', []),
            "{$provider->value}.{$capability}.default",
        );
    }

    public function resolve(GenerationProvider $provider, string $capability, ?string $model): string
    {
        return in_array($model, $this->ids($provider, $capability), true)
            ? $model
            : $this->default($provider, $capability);
    }
}
