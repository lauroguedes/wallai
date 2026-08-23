<?php

namespace App\Models;

use App\Enums\GenerationProvider;
use Database\Factories\AiProviderSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProviderSetting extends Model
{
    /** @use HasFactory<AiProviderSettingFactory> */
    use HasFactory, HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'text_provider',
        'text_model',
        'image_provider',
        'image_model',
        'openai_api_key',
        'gemini_api_key',
        'ollama_url',
        'user_id',
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
        $attribute = $provider->apiKeyAttribute();

        if ($attribute === null) {
            return null;
        }

        $key = $this->getAttribute($attribute);

        return filled($key) ? $key : null;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
