<?php

namespace App\Services;

use App\Ai\Agents\ImagePromptAgent;
use App\Ai\Agents\PromptGenerator;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Enums\GenerationProvider;
use App\Exceptions\MissingAiCredentialsException;
use App\Exceptions\ServiceGeneratorException;
use App\Jobs\GenerateWallpaper;
use App\Models\Wallpaper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;

class WallpaperService
{
    public function __construct(
        private AiProviderSettings $providerSettings,
        private RuntimeAiProvider $runtimeProvider,
        private WorkspaceContext $workspace,
    ) {}

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
        $settings = $this->providerSettings->current();
        $textProvider = $this->providerSettings->textProvider($settings);
        $imageProvider = $this->providerSettings->imageProvider($settings);
        $textModel = $this->providerSettings->textModel($settings, $textProvider);
        $imageModel = $this->providerSettings->imageModel($settings, $imageProvider);

        $this->providerSettings->ensureConfigured([$textProvider, $imageProvider], $settings);

        $this->registerGeneration($sessionId, $jobId);

        GenerateWallpaper::dispatch(
            $sessionId,
            $jobId,
            $prompt,
            $style,
            $deviceType,
            $settings?->getKey(),
            $textProvider,
            $imageProvider,
            $textModel,
            $imageModel,
        )
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
     * Remove every resource owned by a user or browser session.
     */
    public function resetSession(string $sessionId): void
    {
        Cache::lock($this->sessionLockKey($sessionId), 10)->block(5, function () use ($sessionId): void {
            $jobIds = Cache::get($this->jobRegistryKey($sessionId), []);

            if (is_array($jobIds)) {
                foreach ($jobIds as $jobId) {
                    Cache::forget("wallpaper_job:{$jobId}");
                    Cache::put($this->cancelledJobKey((string) $jobId), true, now()->addDay());
                }
            }

            if (! str_starts_with($sessionId, 'user:')) {
                Cache::put($this->resetMarkerKey($sessionId), true, now()->addDay());
            }

            foreach (DeviceType::cases() as $deviceType) {
                Cache::forget("wallpapers:{$sessionId}:{$deviceType->value}");
            }

            Cache::forget("pending_jobs:{$sessionId}");
            Cache::forget($this->jobRegistryKey($sessionId));

            $storedWallpapers = Wallpaper::query()->ownedByWorkspace($sessionId)->get();

            foreach ($storedWallpapers as $wallpaper) {
                Storage::disk('public')->delete($wallpaper->path);
            }

            Wallpaper::query()->ownedByWorkspace($sessionId)->delete();
            Storage::disk('public')->deleteDirectory($this->workspace->storageDirectory($sessionId));
            $this->providerSettings->forget();
        });

        if (! str_starts_with($sessionId, 'user:')) {
            session()->invalidate();
            session()->regenerateToken();
        }
    }

    public function sessionWasReset(string $sessionId): bool
    {
        return Cache::has($this->resetMarkerKey($sessionId));
    }

    public function generationWasCancelled(string $sessionId, string $jobId): bool
    {
        return $this->sessionWasReset($sessionId) || Cache::has($this->cancelledJobKey($jobId));
    }

    /**
     * @param  array{id: string, url: string, path: string, extension: string, style: string}  $wallpaper
     */
    public function completeGeneration(
        string $sessionId,
        string $jobId,
        DeviceType $deviceType,
        array $wallpaper,
    ): void {
        Cache::lock($this->sessionLockKey($sessionId), 10)->block(5, function () use ($sessionId, $jobId, $deviceType, $wallpaper): void {
            if ($this->generationWasCancelled($sessionId, $jobId)) {
                Storage::disk('public')->delete($wallpaper['path']);

                return;
            }

            Wallpaper::query()->updateOrCreate(
                ['id' => $wallpaper['id']],
                [
                    ...$this->workspace->ownerAttributes($sessionId),
                    'device_type' => $deviceType,
                    'style' => $wallpaper['style'],
                    'path' => $wallpaper['path'],
                    'extension' => $wallpaper['extension'],
                ],
            );

            Cache::put("wallpaper_job:{$jobId}", [
                'status' => 'completed',
                'wallpaper' => $wallpaper,
            ], now()->addDay());

            $cacheKey = "wallpapers:{$sessionId}:{$deviceType->value}";
            $wallpapers = Cache::get($cacheKey, []);
            $wallpapers[] = $wallpaper;
            Cache::put($cacheKey, $wallpapers, now()->addDay());

            $this->decrementPendingJobCount($sessionId);
        });
    }

    public function failGeneration(string $sessionId, string $jobId, string $message): void
    {
        Cache::lock($this->sessionLockKey($sessionId), 10)->block(5, function () use ($sessionId, $jobId, $message): void {
            if ($this->generationWasCancelled($sessionId, $jobId)) {
                return;
            }

            Cache::put("wallpaper_job:{$jobId}", [
                'status' => 'failed',
                'message' => $message,
            ], now()->addDay());

            $this->decrementPendingJobCount($sessionId);
        });
    }

    /**
     * Get the result of a specific job.
     *
     * @return array{status: string, wallpaper?: array, message?: string}|null
     */
    public function getJobResult(string $jobId): ?array
    {
        $result = Cache::get("wallpaper_job:{$jobId}");

        if (! is_array($result)) {
            return null;
        }

        if (isset($result['wallpaper']) && is_array($result['wallpaper'])) {
            $result['wallpaper'] = $this->normalizeWallpaperUrl($result['wallpaper']);
        }

        return $result;
    }

    /**
     * Get all wallpapers for a user or session and device type.
     *
     * @return array<int, array{id: string, url: string, path: string, extension: string}>
     */
    public function getSessionWallpapers(string $sessionId, DeviceType|string $deviceType): array
    {
        $deviceValue = $deviceType instanceof DeviceType ? $deviceType->value : $deviceType;
        $cacheKey = "wallpapers:{$sessionId}:{$deviceValue}";
        $wallpapers = Cache::get($cacheKey);

        if (! is_array($wallpapers)) {
            $wallpapers = Wallpaper::query()
                ->ownedByWorkspace($sessionId)
                ->where('device_type', $deviceValue)
                ->oldest()
                ->get()
                ->map(fn (Wallpaper $wallpaper): array => $this->wallpaperPayload($wallpaper))
                ->all();

            Cache::put($cacheKey, $wallpapers, now()->addDay());
        }

        return array_map(
            fn (array $wallpaper): array => $this->normalizeWallpaperUrl($wallpaper),
            $wallpapers,
        );
    }

    /**
     * Delete a wallpaper from storage and its workspace registry.
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

        Wallpaper::query()
            ->ownedByWorkspace($sessionId)
            ->whereKey($wallpaperId)
            ->delete();

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
    public function generateImage(
        string $prompt,
        BackgroundStyle $style,
        DeviceType $deviceType = DeviceType::Mobile,
        ?string $sessionId = null,
        ?string $providerSettingsId = null,
        ?GenerationProvider $textProvider = null,
        ?GenerationProvider $imageProvider = null,
        ?string $textModel = null,
        ?string $imageModel = null,
    ): array {
        try {
            $settings = $this->providerSettings->find($providerSettingsId);
            $textProvider ??= $this->providerSettings->textProvider($settings);
            $imageProvider ??= $this->providerSettings->imageProvider($settings);
            $textModel ??= $this->providerSettings->textModel($settings, $textProvider);
            $imageModel ??= $this->providerSettings->imageModel($settings, $imageProvider);

            $structuredResponse = $this->runtimeProvider->using(
                $textProvider,
                $settings,
                fn (string $provider) => (new ImagePromptAgent($style, $deviceType))->prompt(
                    $prompt,
                    provider: $provider,
                    model: $textModel,
                ),
            );
            $engineeredPrompt = $this->flattenStructuredPrompt($structuredResponse->toArray());

            $response = $this->runtimeProvider->using(
                $imageProvider,
                $settings,
                fn (string $provider) => Image::of($engineeredPrompt)
                    ->when($deviceType === DeviceType::Mobile, fn ($image) => $image->portrait())
                    ->when($deviceType === DeviceType::Desktop, fn ($image) => $image->landscape())
                    ->quality('high')
                    ->timeout(120)
                    ->generate(provider: $provider, model: $imageModel),
            );

            $image = $response->firstImage();
            $extension = $this->getExtension($image->mime);
            $filename = Str::ulid().'.'.$extension;

            $directory = $sessionId ? $this->workspace->storageDirectory($sessionId) : 'wallpapers';
            $path = $directory.'/'.$filename;

            Storage::disk('public')->put($path, $image->content());

            return [
                'id' => $filename,
                'url' => $this->publicWallpaperUrl($path),
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
            $settings = $this->providerSettings->current();
            $textProvider = $this->providerSettings->textProvider($settings);
            $textModel = $this->providerSettings->textModel($settings, $textProvider);
            $this->providerSettings->ensureConfigured([$textProvider], $settings);

            $deviceContext = $deviceType->promptContext();

            $message = "Generate a creative image prompt for a {$style->title()} style {$deviceContext}. "
                ."The style is described as: {$style->description()}";

            if ($userPrompt !== '') {
                $message .= " Use this text as context and inspiration: {$userPrompt}";
            }

            $response = $this->runtimeProvider->using(
                $textProvider,
                $settings,
                fn (string $provider) => (new PromptGenerator)->prompt(
                    $message,
                    provider: $provider,
                    model: $textModel,
                ),
            );

            return trim($response->text);
        } catch (MissingAiCredentialsException $e) {
            throw $e;
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

    /**
     * Keep local wallpaper URLs on the browser's current origin.
     */
    private function publicWallpaperUrl(string $path): string
    {
        if (config('filesystems.disks.public.driver') !== 'local') {
            return Storage::disk('public')->url($path);
        }

        $configuredUrl = (string) config('filesystems.disks.public.url', '/storage');
        $basePath = parse_url($configuredUrl, PHP_URL_PATH) ?: '/storage';

        return '/'.trim($basePath, '/').'/'.ltrim($path, '/');
    }

    /**
     * Repair absolute local URLs already stored by long-running queue workers.
     *
     * @param  array<string, mixed>  $wallpaper
     * @return array<string, mixed>
     */
    private function normalizeWallpaperUrl(array $wallpaper): array
    {
        if (
            isset($wallpaper['path'])
            && is_string($wallpaper['path'])
            && (! isset($wallpaper['url']) || ! str_starts_with((string) $wallpaper['url'], '/'))
        ) {
            $wallpaper['url'] = $this->publicWallpaperUrl($wallpaper['path']);
        }

        return $wallpaper;
    }

    private function registerGeneration(string $sessionId, string $jobId): void
    {
        Cache::lock($this->sessionLockKey($sessionId), 10)->block(5, function () use ($sessionId, $jobId): void {
            Cache::put("pending_jobs:{$sessionId}", $this->getPendingJobCount($sessionId) + 1, now()->addDay());

            $jobIds = Cache::get($this->jobRegistryKey($sessionId), []);
            $jobIds = is_array($jobIds) ? $jobIds : [];
            $jobIds[] = $jobId;

            Cache::put($this->jobRegistryKey($sessionId), array_values(array_unique($jobIds)), now()->addDay());
        });
    }

    private function decrementPendingJobCount(string $sessionId): void
    {
        if ($this->getPendingJobCount($sessionId) > 0) {
            Cache::decrement("pending_jobs:{$sessionId}");
        }
    }

    private function sessionLockKey(string $sessionId): string
    {
        return "wallpaper_session:{$sessionId}:lock";
    }

    private function resetMarkerKey(string $sessionId): string
    {
        return "wallpaper_session:{$sessionId}:reset";
    }

    private function jobRegistryKey(string $sessionId): string
    {
        return "wallpaper_jobs:{$sessionId}";
    }

    private function cancelledJobKey(string $jobId): string
    {
        return "wallpaper_job_cancelled:{$jobId}";
    }

    /**
     * @return array{id: string, url: string, path: string, extension: string, style: string}
     */
    private function wallpaperPayload(Wallpaper $wallpaper): array
    {
        return [
            'id' => $wallpaper->getKey(),
            'url' => $this->publicWallpaperUrl($wallpaper->path),
            'path' => $wallpaper->path,
            'extension' => $wallpaper->extension,
            'style' => $wallpaper->style->value,
        ];
    }
}
