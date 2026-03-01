<?php

namespace App\Services;

use App\Ai\Agents\PromptGenerator;
use App\Enums\ImageType;
use App\Exceptions\ServiceGeneratorException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;

class WallpaperService
{
    /**
     * Generate a wallpaper image from a prompt and style.
     *
     * @return array{id: string, url: string, path: string, extension: string}
     *
     * @throws ServiceGeneratorException
     */
    public function generateImage(string $prompt, string $style): array
    {
        try {
            $engineeredPrompt = $this->buildImagePrompt($prompt, $style);

            $response = Image::of($engineeredPrompt)
                ->portrait()
                ->quality('high')
                ->generate('gemini');

            $image = $response->firstImage();
            $extension = $this->getExtension($image->mime);
            $filename = Str::ulid().'.'.$extension;
            $path = 'wallpapers/'.$filename;

            Storage::disk('public')->put($path, $image->content());

            return [
                'id' => $filename,
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
                'extension' => $extension,
            ];
        } catch (\Throwable $e) {
            throw new ServiceGeneratorException($e->getMessage());
        }
    }

    /**
     * Generate a random creative prompt for a given style.
     *
     * @throws ServiceGeneratorException
     */
    public function generatePrompt(string $style): string
    {
        try {
            $response = (new PromptGenerator)->prompt(
                "Generate a creative image prompt for a {$style} style mobile wallpaper"
            );

            return trim($response->text);
        } catch (\Throwable $e) {
            throw new ServiceGeneratorException($e->getMessage());
        }
    }

    /**
     * Build the engineered prompt combining user input with style templates.
     */
    protected function buildImagePrompt(string $prompt, string $style): string
    {
        $imageType = ImageType::from($style);

        return sprintf(
            config('app.image_generator_system_prompt'),
            $imageType->name,
            $prompt,
            $imageType->prompt()
        );
    }

    /**
     * Get file extension from MIME type.
     */
    protected function getExtension(?string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
    }
}
