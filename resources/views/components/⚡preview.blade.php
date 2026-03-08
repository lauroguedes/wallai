<?php

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    use Toast;

    /** @var array<int, array{id: string, url: string, path: string, extension: string, style: string}> */
    public array $wallpapers = [];

    public ?array $activeWallpaper = null;

    public string $deviceType = 'mobile';

    /** @var string[] */
    public array $pendingJobs = [];

    public string $loadingPhrase = '';

    public bool $showLoading = false;

    protected const array LOADING_PHRASES = [
        '🎨 Painting your masterpiece...',
        '✨ Sprinkling some AI magic...',
        '🌌 Crafting your universe...',
        '🖌️ Mixing the perfect colors...',
        '🔮 Conjuring something beautiful...',
    ];

    public function mount(string $deviceType, WallpaperService $service): void
    {
        $this->deviceType = $deviceType;
        $this->wallpapers = $service->getSessionWallpapers(session()->getId(), $this->deviceType);

        if (! empty($this->wallpapers)) {
            $this->activeWallpaper = end($this->wallpapers);
        }
    }

    #[On('wallpaper-job-dispatched')]
    public function onJobDispatched(string $jobId, string $deviceType): void
    {
        if ($deviceType !== $this->deviceType) {
            return;
        }

        $this->pendingJobs[] = $jobId;
        $this->loadingPhrase = self::LOADING_PHRASES[array_rand(self::LOADING_PHRASES)];
        $this->showLoading = true;
    }

    public function checkPendingJobs(WallpaperService $service): void
    {
        $stillPending = [];

        foreach ($this->pendingJobs as $jobId) {
            $result = $service->getJobResult($jobId);

            if ($result === null) {
                $stillPending[] = $jobId;

                continue;
            }

            if ($result['status'] === 'completed') {
                $this->wallpapers = $service->getSessionWallpapers(session()->getId(), $this->deviceType);
                $this->activeWallpaper = $result['wallpaper'];
                $this->showLoading = false;
                $this->success('Your wallpaper is ready!');
            } elseif ($result['status'] === 'failed') {
                $this->error($result['message'] ?? 'Generation failed. Please try again.');
            }
        }

        $this->pendingJobs = $stillPending;

        if (! empty($this->pendingJobs)) {
            $this->loadingPhrase = self::LOADING_PHRASES[array_rand(self::LOADING_PHRASES)];
        }
    }

    public function selectWallpaper(int $index): void
    {
        if (isset($this->wallpapers[$index])) {
            $this->activeWallpaper = $this->wallpapers[$index];
            $this->showLoading = false;
        }
    }

    public function selectPendingJob(): void
    {
        $this->showLoading = true;
    }

    public function deleteWallpaper(string $wallpaperId, WallpaperService $service): void
    {
        $service->deleteWallpaper(session()->getId(), $wallpaperId, $this->deviceType);
        $this->wallpapers = $service->getSessionWallpapers(session()->getId(), $this->deviceType);

        if ($this->activeWallpaper && $this->activeWallpaper['id'] === $wallpaperId) {
            $this->activeWallpaper = ! empty($this->wallpapers) ? end($this->wallpapers) : null;
        }
    }

    public function downloadImage(): ?StreamedResponse
    {
        try {
            $wallpaper = $this->activeWallpaper;

            if (! $wallpaper) {
                return null;
            }

            $path = $wallpaper['path'];
            $extension = $wallpaper['extension'];
            $content = Storage::disk('public')->get($path);

            $style = BackgroundStyle::from($wallpaper['style']);
            $hash = substr($wallpaper['id'], 0, 8);
            $downloadName = $style->slug().'_'.$this->deviceType.'_'.$hash.'.'.$extension;

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $downloadName);
        } catch (\Throwable $e) {
            $exception = ServiceGeneratorException::downloadFailed($e, [
                'wallpaper' => $this->activeWallpaper,
            ]);

            report($exception);
            $this->error($exception->getUserMessage());

            return null;
        }
    }
}; ?>

<div @if(count($pendingJobs) > 0) wire:poll.5s="checkPendingJobs" @endif
     class="flex flex-col items-center justify-center gap-4 w-full h-full">

    {{-- Sync active wallpaper URL to parent for frosted glass background --}}
    <div x-init="$dispatch('wallpaper-bg-updated', { url: {{ Js::from($activeWallpaper['url'] ?? null) }}, deviceType: '{{ $deviceType }}' })"
         wire:key="bg-sync-{{ $activeWallpaper['id'] ?? 'none' }}"
         class="hidden"></div>

    @php
        $isLoading = $showLoading && count($pendingJobs) > 0;
        $hasWallpaper = $activeWallpaper && !$showLoading;
        $isEmpty = !$showLoading || count($pendingJobs) === 0;
        $hasThumbnails = count($wallpapers) > 0 || count($pendingJobs) > 0;
        $isWallpaperActive = fn ($wallpaper) => $activeWallpaper && $activeWallpaper['id'] === $wallpaper['id'] && !$showLoading;
    @endphp

    @if($deviceType === 'mobile')
        {{-- Fullscreen mobile — no mockup frame (visible on mobile devices only) --}}
        <div class="md:hidden w-full h-screen relative bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center">
            @if($isLoading)
                <x-preview.loading-overlay :loadingPhrase="$loadingPhrase" />
            @endif
            @if($hasWallpaper)
                <x-preview.wallpaper-image :url="$activeWallpaper['url']" />
                <div class="absolute top-6 right-6 flex gap-2 z-20">
                    <x-button wire:click="downloadImage" class="btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                    <button wire:click="downloadImage"
                            x-on:click="$nextTick(() => document.getElementById('wallpaper-instructions-modal').showModal())"
                            class="btn btn-primary btn-circle">
                        <x-icon name="lucide.smartphone" class="w-5 h-5" />
                    </button>
                </div>
            @elseif($isEmpty)
                <x-preview.empty-state />
            @endif

            @if($hasThumbnails)
                <div class="absolute bottom-20 left-0 right-0 px-4 z-20">
                    <div class="flex flex-row gap-2 overflow-x-auto py-2 px-2">
                        @foreach($wallpapers as $index => $wallpaper)
                            <x-preview.thumbnail :wallpaper="$wallpaper" :index="$index" :isActive="$isWallpaperActive($wallpaper)" size="w-14 h-14" keyPrefix="thumb-fs" />
                        @endforeach
                        @foreach($pendingJobs as $jobId)
                            <x-preview.pending-thumbnail :jobId="$jobId" :isLoading="$showLoading" size="w-14 h-14" keyPrefix="pending-fs" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Mockup mobile — phone frame (visible on tablet/desktop only) --}}
        <div class="hidden md:flex flex-row items-center gap-3 h-[85vh]">
            <div class="mockup-phone border-primary h-full w-auto max-w-none group/mockup">
                <div class="mockup-phone-camera"></div>
                <div class="mockup-phone-display">
                    <div class="relative w-full h-full bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center">
                        @if($isLoading)
                            <x-preview.loading-overlay :loadingPhrase="$loadingPhrase" />
                        @endif
                        @if($hasWallpaper)
                            <x-preview.wallpaper-image :url="$activeWallpaper['url']" />
                            <x-button wire:click="downloadImage" class="absolute bottom-6 right-4 btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                        @elseif($isEmpty)
                            <x-preview.empty-state />
                        @endif
                        <div class="absolute z-11 right-1 transition-all duration-300 ease-out
                                    opacity-0 translate-x-3 blur-sm pointer-events-none
                                    group-hover/mockup:opacity-100 group-hover/mockup:translate-x-0 group-hover/mockup:blur-none group-hover/mockup:pointer-events-auto">
                            @if($hasThumbnails)
                                <div class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden max-h-80 py-2 px-2">
                                    @foreach($wallpapers as $index => $wallpaper)
                                        <x-preview.thumbnail :wallpaper="$wallpaper" :index="$index" :isActive="$isWallpaperActive($wallpaper)" />
                                    @endforeach
                                    @foreach($pendingJobs as $jobId)
                                        <x-preview.pending-thumbnail :jobId="$jobId" :isLoading="$showLoading" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Desktop layout: monitor mockup --}}
        <div class="flex flex-col items-center gap-3 w-full">
            <x-mockup-monitor>
                <div class="relative bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center aspect-video group/mockup">
                    @if($isLoading)
                        <x-preview.loading-overlay :loadingPhrase="$loadingPhrase" />
                    @endif
                    @if($hasWallpaper)
                        <x-preview.wallpaper-image :url="$activeWallpaper['url']" />
                        <x-button wire:click="downloadImage" class="absolute bottom-6 right-4 btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                    @elseif($isEmpty)
                        <x-preview.empty-state />
                    @endif
                    <div class="absolute z-11 bottom-6 left-4 transition-all duration-300 ease-out
                                opacity-0 translate-y-3 blur-sm pointer-events-none
                                group-hover/mockup:opacity-100 group-hover/mockup:translate-y-0 group-hover/mockup:blur-none group-hover/mockup:pointer-events-auto">
                        @if($hasThumbnails)
                            <div class="flex flex-row gap-2 overflow-x-auto overflow-x-hidden max-w-80 py-2 px-2">
                                @foreach($wallpapers as $index => $wallpaper)
                                    <x-preview.thumbnail :wallpaper="$wallpaper" :index="$index" :isActive="$isWallpaperActive($wallpaper)" />
                                @endforeach
                                @foreach($pendingJobs as $jobId)
                                    <x-preview.pending-thumbnail :jobId="$jobId" :isLoading="$showLoading" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </x-mockup-monitor>
        </div>
    @endif

    <x-preview.wallpaper-instructions-modal />
</div>
