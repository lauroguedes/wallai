<?php

namespace App\Models;

use App\Enums\GenerationProvider;
use Database\Factories\AiProviderSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProviderSetting extends Model
{
    /** @use HasFactory<AiProviderSettingFactory> */
    use HasFactory, HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'text_provider',
        'image_provider',
        'openai_api_key',
        'gemini_api_key',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'openai_api_key',
        'gemini_api_key',
    ];

    protected $attributes = [
        'text_provider' => GenerationProvider::Gemini->value,
        'image_provider' => GenerationProvider::Gemini->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'text_provider' => GenerationProvider::class,
            'image_provider' => GenerationProvider::class,
            'openai_api_key' => 'encrypted',
            'gemini_api_key' => 'encrypted',
        ];
    }

    public function apiKeyFor(GenerationProvider $provider): ?string
    {
        $key = $this->getAttribute($provider->apiKeyAttribute());

        return filled($key) ? $key : null;
    }
}
