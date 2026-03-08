<?php

namespace App\Services;

use App\Ai\Agents\ImagePromptAgent;
use App\Ai\Agents\PromptGenerator;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Jobs\GenerateWallpaper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;

class WallpaperService
{
    /**
     * Maximum number of concurrent pending jobs per session.
     */
    public static function maxPendingJobs(): int
    {
        return (int) config('wallpaper.queue_processes', 3);
    }

    /**
     * Dispatch a wallpaper generation job to the queue.
     */
    public function dispatchGeneration(string $sessionId, string $prompt, BackgroundStyle $style, DeviceType $deviceType): string
    {
        $jobId = (string) Str::ulid();

        Cache::put("pending_jobs:{$sessionId}", $this->getPendingJobCount($sessionId) + 1, now()->addDay());

        GenerateWallpaper::dispatch($sessionId, $jobId, $prompt, $style, $deviceType)
            ->onQueue("wallpapers-{$deviceType->value}");

        return $jobId;
    }

    /**
     * Get the number of pending jobs for a session.
     */
    public function getPendingJobCount(string $sessionId): int
    {
        return (int) Cache::get("pending_jobs:{$sessionId}", 0);
    }

    /**
     * Get the result of a specific job.
     *
     * @return array{status: string, wallpaper?: array, message?: string}|null
     */
    public function getJobResult(string $jobId): ?array
    {
        return Cache::get("wallpaper_job:{$jobId}");
    }

    /**
     * Get all wallpapers for a session and device type.
     *
     * @return array<int, array{id: string, url: string, path: string, extension: string}>
     */
    public function getSessionWallpapers(string $sessionId, DeviceType|string $deviceType): array
    {
        $deviceValue = $deviceType instanceof DeviceType ? $deviceType->value : $deviceType;

        return Cache::get("wallpapers:{$sessionId}:{$deviceValue}", []);
    }

    /**
     * Delete a wallpaper from storage and the session registry.
     */
    public function deleteWallpaper(string $sessionId, string $wallpaperId, DeviceType|string $deviceType): array
    {
        $deviceValue = $deviceType instanceof DeviceType ? $deviceType->value : $deviceType;
        $wallpapers = $this->getSessionWallpapers($sessionId, $deviceValue);

        $toDelete = array_filter($wallpapers, fn (array $w) => $w['id'] === $wallpaperId);
        foreach ($toDelete as $wallpaper) {
            Storage::disk('public')->delete($wallpaper['path']);
        }

        $wallpapers = array_values(array_filter($wallpapers, fn (array $w) => $w['id'] !== $wallpaperId));

        Cache::put("wallpapers:{$sessionId}:{$deviceValue}", $wallpapers, now()->addDay());

        return $wallpapers;
    }

    /**
     * Generate a wallpaper image from a prompt, style, and device type.
     *
     * @return array{id: string, url: string, path: string, extension: string, style: string}
     *
     * @throws ServiceGeneratorException
     */
    public function generateImage(string $prompt, BackgroundStyle $style, DeviceType $deviceType = DeviceType::Mobile, ?string $sessionId = null): array
    {
        try {
            $structuredResponse = new ImagePromptAgent($style, $deviceType)->prompt($prompt);
            $engineeredPrompt = $this->flattenStructuredPrompt($structuredResponse->toArray());

            $response = Image::of($engineeredPrompt)
                ->when($deviceType === DeviceType::Mobile, fn ($image) => $image->portrait())
                ->when($deviceType === DeviceType::Desktop, fn ($image) => $image->landscape())
                ->quality('high')
                ->timeout(120)
                ->generate();

            $image = $response->firstImage();
            $extension = $this->getExtension($image->mime);
            $filename = Str::ulid().'.'.$extension;

            $directory = $sessionId ? "wallpapers/{$sessionId}" : 'wallpapers';
            $path = $directory.'/'.$filename;

            Storage::disk('public')->put($path, $image->content());

            return [
                'id' => $filename,
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
                'extension' => $extension,
                'style' => $style->value,
            ];
        } catch (\Throwable $e) {
            throw ServiceGeneratorException::imageGeneration($e, [
                'prompt' => $prompt,
                'style' => $style->value,
                'device_type' => $deviceType->value,
            ]);
        }
    }

    /**
     * Generate a random creative prompt for a given style and device type.
     *
     * @throws ServiceGeneratorException
     */
    public function generatePrompt(BackgroundStyle $style, DeviceType $deviceType = DeviceType::Mobile, string $userPrompt = ''): string
    {
        try {
            $deviceContext = $deviceType->promptContext();

            $message = "Generate a creative image prompt for a {$style->title()} style {$deviceContext}. "
                ."The style is described as: {$style->description()}";

            if ($userPrompt !== '') {
                $message .= " Use this text as context and inspiration: {$userPrompt}";
            }

            $response = (new PromptGenerator)->prompt($message);

            return trim($response->text);
        } catch (\Throwable $e) {
            throw ServiceGeneratorException::promptGeneration($e, [
                'style' => $style->value,
                'device_type' => $deviceType->value,
            ]);
        }
    }

    /**
     * Flatten a structured image prompt response into a natural language string.
     *
     * @param  array<string, mixed>  $structured
     */
    protected function flattenStructuredPrompt(array $structured): string
    {
        $parts = [];

        if (isset($structured['subject'])) {
            $subject = $structured['subject'];
            $parts[] = ucfirst($subject['entity_type'] ?? 'scene').':';

            if (! empty($subject['description'])) {
                $parts[] = implode(', ', $subject['description']).'.';
            }

            if (! empty($subject['arrangement'])) {
                $parts[] = $subject['arrangement'].'.';
            }

            if (! empty($subject['materials'])) {
                $materials = array_filter($subject['materials'], fn ($v) => $v && strtolower($v) !== 'none');
                if (! empty($materials)) {
                    $parts[] = 'Materials: '.implode(', ', $materials).'.';
                }
            }
        }

        if (isset($structured['scene'])) {
            $scene = $structured['scene'];

            if (! empty($scene['environment'])) {
                $parts[] = 'Environment: '.$scene['environment'].'.';
            }

            if (! empty($scene['lighting'])) {
                $lighting = $scene['lighting'];
                $parts[] = 'Lighting: '.($lighting['source'] ?? '')
                    .' from '.($lighting['direction'] ?? '')
                    .', '.($lighting['atmosphere'] ?? '').' atmosphere.';
            }

            if (! empty($scene['objects'])) {
                $parts[] = 'Scene elements: '.implode(', ', $scene['objects']).'.';
            }
        }

        if (isset($structured['technical_camera'])) {
            $camera = $structured['technical_camera'];
            $parts[] = 'Shot with '.($camera['lens'] ?? '').' lens, '
                .($camera['aperture'] ?? '').', ISO '.($camera['iso'] ?? 100).', '
                .($camera['camera_angle'] ?? 'eye-level').' angle.';
        }

        if (isset($structured['global_settings'])) {
            $settings = $structured['global_settings'];
            $parts[] = ucfirst($settings['quality_mode'] ?? 'high').' quality, '
                .($settings['resolution'] ?? '').' resolution.';
        }

        if (isset($structured['text_rendering']) && ! empty($structured['text_rendering']['content'])) {
            $text = $structured['text_rendering'];
            $parts[] = 'Text: "'.$text['content'].'" in '
                .($text['font_style'] ?? 'sans-serif')
                .' style, placed at '.($text['placement'] ?? 'center').'.';
        }

        if (! empty($structured['negative_prompt'])) {
            $parts[] = 'Avoid: '.implode(', ', $structured['negative_prompt']).'.';
        }

        return implode(' ', $parts);
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
