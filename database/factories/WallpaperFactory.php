<?php

namespace Database\Factories;

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Models\Wallpaper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallpaper>
 */
class WallpaperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid().'.png',
            'session_id' => fake()->uuid(),
            'device_type' => DeviceType::Mobile,
            'style' => BackgroundStyle::NaturalLandscape,
            'path' => 'wallpapers/'.fake()->uuid().'.png',
            'extension' => 'png',
        ];
    }
}
