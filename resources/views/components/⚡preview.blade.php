<?php

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

    /** @var array<int, array{id: string, url: string, path: string, extension: string}> */
    public array $wallpapers = [];

    public ?array $activeWallpaper = null;

    public string $deviceType = 'mobile';

    /** @var string[] */
    public array $pendingJobs = [];

    public string $loadingPhrase = '';

    protected const array LOADING_PHRASES = [
        '🎨 Painting your masterpiece...',
        '✨ Sprinkling some AI magic...',
        '🌌 Crafting your universe...',
        '🖌️ Mixing the perfect colors...',
        '🔮 Conjuring something beautiful...',
    ];

    public function mount(WallpaperService $service): void
    {
        $this->wallpapers = $service->getSessionWallpapers(session()->getId());

        if (! empty($this->wallpapers)) {
            $this->activeWallpaper = end($this->wallpapers);
        }
    }

    #[On('wallpaper-job-dispatched')]
    public function onJobDispatched(string $jobId): void
    {
        $this->pendingJobs[] = $jobId;
        $this->loadingPhrase = self::LOADING_PHRASES[array_rand(self::LOADING_PHRASES)];
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
                $this->wallpapers = $service->getSessionWallpapers(session()->getId());
                $this->activeWallpaper = $result['wallpaper'];
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
        }
    }

    public function deleteWallpaper(string $wallpaperId, WallpaperService $service): void
    {
        $service->deleteWallpaper(session()->getId(), $wallpaperId);
        $this->wallpapers = $service->getSessionWallpapers(session()->getId());

        if ($this->activeWallpaper && $this->activeWallpaper['id'] === $wallpaperId) {
            $this->activeWallpaper = ! empty($this->wallpapers) ? end($this->wallpapers) : null;
        }
    }

    public function setDeviceType(string $type): void
    {
        $this->deviceType = $type;
        $this->dispatch('device-type-changed', $type);
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
            $prefix = DeviceType::from($this->deviceType)->filenamePrefix();

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $prefix.'.'.$extension);
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
    {{-- Device type toggle --}}
    <div class="join">
        <button wire:click="setDeviceType('mobile')"
                class="btn join-item {{ $deviceType === 'mobile' ? 'btn-active' : '' }}">
            <x-icon name="lucide.smartphone" class="w-5 h-5" />
        </button>
        <button wire:click="setDeviceType('desktop')"
                class="btn join-item {{ $deviceType === 'desktop' ? 'btn-active' : '' }}">
            <x-icon name="lucide.monitor" class="w-5 h-5" />
        </button>
    </div>

    {{-- Mobile mockup --}}
    @if($deviceType === 'mobile')
        <div class="mockup-phone border-primary h-[85vh] w-auto max-w-none">
            <div class="mockup-phone-camera"></div>
            <div class="mockup-phone-display">
                <div class="relative w-full h-full bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center">
                    @if(count($pendingJobs) > 0)
                        <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-linear-to-tr from-slate-700 to-gray-900">
                            <span class="loading loading-spinner loading-lg text-primary"></span>
                            <span class="text-sm text-base-content/70 animate-pulse">{{ $loadingPhrase }}</span>
                        </div>
                    @endif
                    @if($activeWallpaper)
                        <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                             src="{{ $activeWallpaper['url'] }}" />
                        <div class="absolute bottom-6 right-4 flex gap-2">
                            <x-button wire:click="deleteWallpaper('{{ $activeWallpaper['id'] }}')"
                                      class="btn-error btn-circle btn-sm" icon="lucide.trash-2" spinner />
                            <x-button wire:click="downloadImage"
                                      class="btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                        </div>
                    @else
                        <div class="opacity-20">
                            <livewire:logo />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- Desktop mockup --}}
        <div class="mockup-window bg-base-100 border border-base-300 w-full">
            <div class="relative bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center aspect-video">
                @if(count($pendingJobs) > 0)
                    <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-linear-to-tr from-slate-700 to-gray-900">
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                        <span class="text-sm text-base-content/70 animate-pulse">{{ $loadingPhrase }}</span>
                    </div>
                @endif
                @if($activeWallpaper)
                    <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                         src="{{ $activeWallpaper['url'] }}" />
                    <div class="absolute bottom-6 right-4 flex gap-2">
                        <x-button wire:click="deleteWallpaper('{{ $activeWallpaper['id'] }}')"
                                  class="btn-error btn-circle btn-sm" icon="lucide.trash-2" spinner />
                        <x-button wire:click="downloadImage"
                                  class="btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                    </div>
                @else
                    <div class="opacity-20">
                        <livewire:logo />
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Thumbnail gallery --}}
    @if(count($wallpapers) > 0 || count($pendingJobs) > 0)
        <div class="flex flex-row gap-2 overflow-x-auto max-w-full py-2 px-1">
            @foreach($wallpapers as $index => $wallpaper)
                <button wire:click="selectWallpaper({{ $index }})"
                        class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all hover:scale-105
                               {{ $activeWallpaper && $activeWallpaper['id'] === $wallpaper['id'] ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }}">
                    <img src="{{ $wallpaper['url'] }}" alt="Thumbnail" class="object-cover w-full h-full" loading="lazy" />
                </button>
            @endforeach

            @foreach($pendingJobs as $jobId)
                <div class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 border-base-300 skeleton"></div>
            @endforeach
        </div>
    @endif
</div>
