<?php

namespace App\Jobs;

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Enums\GenerationProvider;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        public ?string $providerSettingsId = null,
        public GenerationProvider $textProvider = GenerationProvider::Gemini,
        public GenerationProvider $imageProvider = GenerationProvider::Gemini,
        public ?string $textModel = null,
        public ?string $imageModel = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WallpaperService $service): void
    {
        if ($service->generationWasCancelled($this->sessionId, $this->jobId)) {
            return;
        }

        $result = $service->generateImage(
            $this->prompt,
            $this->style,
            $this->deviceType,
            $this->sessionId,
            $this->providerSettingsId,
            $this->textProvider,
            $this->imageProvider,
            $this->textModel,
            $this->imageModel,
        );

        $service->completeGeneration($this->sessionId, $this->jobId, $this->deviceType, $result);
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

        app(WallpaperService::class)->failGeneration(
            $this->sessionId,
            $this->jobId,
            ServiceGeneratorException::imageGeneration(
                $exception ?? new \RuntimeException('Unknown error'),
            )->getUserMessage(),
        );
    }
}
