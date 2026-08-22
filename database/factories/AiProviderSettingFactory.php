<?php

namespace Database\Factories;

use App\Enums\GenerationProvider;
use App\Models\AiProviderSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiProviderSetting>
 */
class AiProviderSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text_provider' => GenerationProvider::Gemini,
            'text_model' => 'gemini-3.7-flash',
            'image_provider' => GenerationProvider::Gemini,
            'image_model' => 'gemini-3.1-flash-image-preview',
            'openai_api_key' => fake()->uuid(),
            'gemini_api_key' => fake()->uuid(),
        ];
    }
}
