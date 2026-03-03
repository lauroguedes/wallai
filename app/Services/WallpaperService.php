<?php

namespace App\Services;

use App\Ai\Agents\PromptGenerator;
use App\Enums\DeviceType;
use App\Enums\ImageType;
use App\Exceptions\ServiceGeneratorException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;

class WallpaperService
{
    private const IMAGE_PROMPT_TEMPLATE = 'Create a stunning %s %s background image: %s. %s orientation. High resolution with rich detail and vibrant colors. Generate ONLY the artwork itself — do NOT include any phone UI elements such as status bars, wifi icons, battery indicators, signal bars, clock, home bar, navigation buttons, or any device overlay. The output must be a clean image with no text or interface elements. %s';

    /**
     * Generate a wallpaper image from a prompt, style, and device type.
     *
     * @return array{id: string, url: string, path: string, extension: string}
     *
     * @throws ServiceGeneratorException
     */
    public function generateImage(string $prompt, string $style, DeviceType $deviceType = DeviceType::Mobile): array
    {
        try {
            $engineeredPrompt = $this->buildImagePrompt($prompt, $style, $deviceType);

            $response = Image::of($engineeredPrompt)
                ->when($deviceType === DeviceType::Mobile, fn ($image) => $image->portrait())
                ->when($deviceType === DeviceType::Desktop, fn ($image) => $image->landscape())
                ->quality('high')
                ->timeout(120)
                ->generate();

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
            throw ServiceGeneratorException::imageGeneration($e, [
                'prompt' => $prompt,
                'style' => $style,
                'device_type' => $deviceType->value,
            ]);
        }
    }

    /**
     * Generate a random creative prompt for a given style and device type.
     *
     * @throws ServiceGeneratorException
     */
    public function generatePrompt(string $style, DeviceType $deviceType = DeviceType::Mobile): string
    {
        try {
            $deviceContext = $deviceType->promptContext();

            $response = (new PromptGenerator)->prompt(
                "Generate a creative image prompt for a {$style} style {$deviceContext}"
            );

            return trim($response->text);
        } catch (\Throwable $e) {
            throw ServiceGeneratorException::promptGeneration($e, [
                'style' => $style,
                'device_type' => $deviceType->value,
            ]);
        }
    }

    /**
     * Build the engineered prompt combining user input with style and device templates.
     */
    protected function buildImagePrompt(string $prompt, string $style, DeviceType $deviceType): string
    {
        $imageType = ImageType::from($style);

        return sprintf(
            self::IMAGE_PROMPT_TEMPLATE,
            $imageType->name,
            $deviceType->promptContext(),
            $prompt,
            ucfirst($deviceType->orientation()),
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
