<?php

namespace Database\Factories;

use App\Enums\ApplicationMode;
use App\Models\ApplicationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationSetting>
 */
class ApplicationSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mode' => ApplicationMode::Session,
            'installed_at' => now(),
        ];
    }
}
