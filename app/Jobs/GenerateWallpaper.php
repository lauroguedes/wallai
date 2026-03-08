<?php

namespace App\Jobs;

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateWallpaper implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $sessionId,
        public string $jobId,
        public string $prompt,
        public BackgroundStyle $style,
        public DeviceType $deviceType,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WallpaperService $service): void
    {
        $result = $service->generateImage($this->prompt, $this->style, $this->deviceType, $this->sessionId);

        Cache::put("wallpaper_job:{$this->jobId}", [
            'status' => 'completed',
            'wallpaper' => $result,
        ], now()->addDay());

        $cacheKey = "wallpapers:{$this->sessionId}:{$this->deviceType->value}";
        Cache::lock("{$cacheKey}:lock", 10)->block(5, function () use ($cacheKey, $result) {
            $existing = Cache::get($cacheKey, []);
            $existing[] = $result;
            Cache::put($cacheKey, $existing, now()->addDay());
        });

        Cache::decrement("pending_jobs:{$this->sessionId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Wallpaper generation failed for job {$this->jobId}", [
            'session_id' => $this->sessionId,
            'prompt' => $this->prompt,
            'exception' => $exception?->getMessage(),
        ]);

        Cache::put("wallpaper_job:{$this->jobId}", [
            'status' => 'failed',
            'message' => ServiceGeneratorException::imageGeneration($exception ?? new \RuntimeException('Unknown error'))->getUserMessage(),
        ], now()->addDay());

        Cache::decrement("pending_jobs:{$this->sessionId}");
    }
}
