<?php

namespace App\Enums;

enum GenerationProvider: string
{
    public const TEXT = 'text';

    public const IMAGE = 'image';

    case OpenAI = 'openai';
    case Gemini = 'gemini';
    case Ollama = 'ollama';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::Gemini => 'Google Gemini',
            self::Ollama => 'Ollama',
        };
    }

    public function supports(string $capability): bool
    {
        return $this->modelDefinition($capability) !== null;
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function modelOptions(string $capability): array
    {
        return $this->modelDefinition($capability)['options'] ?? [];
    }

    public function defaultModel(string $capability): ?string
    {
        return $this->modelDefinition($capability)['default'] ?? null;
    }

    public function allowsCustomModel(string $capability): bool
    {
        return $this->modelDefinition($capability)['custom'] ?? false;
    }

    public function requiresApiKey(): bool
    {
        return $this !== self::Ollama;
    }

    public function apiKeyAttribute(): ?string
    {
        return match ($this) {
            self::OpenAI => 'openai_api_key',
            self::Gemini => 'gemini_api_key',
            self::Ollama => null,
        };
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public static function options(?string $capability = null): array
    {
        return array_map(
            fn (self $provider): array => [
                'id' => $provider->value,
                'name' => $provider->label(),
            ],
            array_values(array_filter(
                self::cases(),
                fn (self $provider): bool => $capability === null || $provider->supports($capability),
            )),
        );
    }

    /**
     * @return array{default: string, options: array<int, array{id: string, name: string}>, custom?: bool}|null
     */
    private function modelDefinition(string $capability): ?array
    {
        return match ($capability) {
            self::TEXT => match ($this) {
                self::OpenAI => [
                    'default' => 'gpt-5.6-terra',
                    'options' => [
                        ['id' => 'gpt-5.6-sol', 'name' => 'GPT-5.6 Sol (highest capability)'],
                        ['id' => 'gpt-5.6-terra', 'name' => 'GPT-5.6 Terra (balanced)'],
                        ['id' => 'gpt-5.6-luna', 'name' => 'GPT-5.6 Luna (economy)'],
                    ],
                ],
                self::Gemini => [
                    'default' => 'gemini-3.7-flash',
                    'options' => [
                        ['id' => 'gemini-3.7-flash', 'name' => 'Gemini 3.7 Flash (SDK default)'],
                        ['id' => 'gemini-3.5-flash-lite', 'name' => 'Gemini 3.5 Flash-Lite (economy)'],
                    ],
                ],
                self::Ollama => [
                    'default' => 'llama3.1:8b',
                    'custom' => true,
                    'options' => [],
                ],
            },
            self::IMAGE => match ($this) {
                self::OpenAI => [
                    'default' => 'gpt-image-2',
                    'options' => [
                        ['id' => 'gpt-image-2', 'name' => 'GPT Image 2 (SDK default)'],
                    ],
                ],
                self::Gemini => [
                    'default' => 'gemini-3.1-flash-image-preview',
                    'options' => [
                        ['id' => 'gemini-3.1-flash-image-preview', 'name' => 'Gemini 3.1 Flash Image Preview (SDK default)'],
                    ],
                ],
                self::Ollama => null,
            },
            default => null,
        };
    }
}
