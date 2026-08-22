<?php

namespace App\Enums;

enum GenerationProvider: string
{
    case OpenAI = 'openai';
    case Gemini = 'gemini';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::Gemini => 'Google Gemini',
        };
    }

    public function apiKeyAttribute(): string
    {
        return match ($this) {
            self::OpenAI => 'openai_api_key',
            self::Gemini => 'gemini_api_key',
        };
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $provider): array => [
                'id' => $provider->value,
                'name' => $provider->label(),
            ],
            self::cases(),
        );
    }
}
