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

    @if($deviceType === 'mobile')
        {{-- Fullscreen mobile — no mockup frame (visible on mobile devices only) --}}
        <div class="md:hidden w-full h-screen relative bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center">
            @if($showLoading && count($pendingJobs) > 0)
                <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-linear-to-tr from-slate-700 to-gray-900">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <span class="text-sm text-base-content/70 animate-pulse">{{ $loadingPhrase }}</span>
                </div>
            @endif
            @if($activeWallpaper && !$showLoading)
                <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                     src="{{ $activeWallpaper['url'] }}" />
                <div class="absolute bottom-6 right-4 flex gap-2 z-20">
                    <x-button wire:click="downloadImage"
                              class="btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                    <button wire:click="downloadImage"
                            x-on:click="$nextTick(() => document.getElementById('wallpaper-instructions-modal').showModal())"
                            class="btn btn-primary btn-circle">
                        <x-icon name="lucide.smartphone" class="w-5 h-5" />
                    </button>
                </div>
            @elseif(!$showLoading || count($pendingJobs) === 0)
                <div class="opacity-20">
                    <livewire:logo />
                </div>
            @endif

            {{-- Horizontal thumbnails at bottom --}}
            @if(count($wallpapers) > 0 || count($pendingJobs) > 0)
                <div class="absolute bottom-20 left-0 right-0 px-4 z-20">
                    <div class="flex flex-row gap-2 overflow-x-auto py-2 px-2">
                        @foreach($wallpapers as $index => $wallpaper)
                            <div class="relative shrink-0 group" wire:key="thumb-fs-{{ $wallpaper['id'] }}">
                                <button wire:click="selectWallpaper({{ $index }})"
                                        class="w-14 h-14 rounded-lg overflow-hidden border-2 transition-all hover:scale-105
                                               {{ $activeWallpaper && $activeWallpaper['id'] === $wallpaper['id'] && !$showLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }}">
                                    <img src="{{ $wallpaper['url'] }}" alt="Thumbnail" class="object-cover w-full h-full" loading="lazy" />
                                </button>
                                <button wire:click="deleteWallpaper('{{ $wallpaper['id'] }}')"
                                        class="absolute -top-1.5 -right-1.5 btn btn-error btn-circle btn-xs opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    <x-icon name="lucide.trash" class="w-3 h-3" />
                                </button>
                            </div>
                        @endforeach

                        @foreach($pendingJobs as $jobId)
                            <button wire:click="selectPendingJob" wire:key="pending-fs-{{ $jobId }}"
                                    class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 transition-all
                                           {{ $showLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }} skeleton">
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Mockup mobile — phone frame (visible on tablet/desktop only) --}}
        <div class="hidden md:flex flex-row items-center gap-3 h-[85vh]">
            {{-- Phone mockup --}}
            <div class="mockup-phone border-primary h-full w-auto max-w-none">
                <div class="mockup-phone-camera"></div>
                <div class="mockup-phone-display">
                    <div class="relative w-full h-full bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center">
                        @if($showLoading && count($pendingJobs) > 0)
                            <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-linear-to-tr from-slate-700 to-gray-900">
                                <span class="loading loading-spinner loading-lg text-primary"></span>
                                <span class="text-sm text-base-content/70 animate-pulse">{{ $loadingPhrase }}</span>
                            </div>
                        @endif
                        @if($activeWallpaper && !$showLoading)
                            <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                                 src="{{ $activeWallpaper['url'] }}" />
                            <x-button wire:click="downloadImage"
                                      class="absolute bottom-6 right-4 btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                        @elseif(!$showLoading || count($pendingJobs) === 0)
                            <div class="opacity-20">
                                <livewire:logo />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Vertical thumbnails on the right --}}
            @if(count($wallpapers) > 0 || count($pendingJobs) > 0)
                <div class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden max-h-full py-2 px-2">
                    @foreach($wallpapers as $index => $wallpaper)
                        <div class="relative shrink-0 group" wire:key="thumb-{{ $wallpaper['id'] }}">
                            <button wire:click="selectWallpaper({{ $index }})"
                                    class="w-16 h-16 rounded-lg overflow-hidden border-2 transition-all hover:scale-105
                                           {{ $activeWallpaper && $activeWallpaper['id'] === $wallpaper['id'] && !$showLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }}">
                                <img src="{{ $wallpaper['url'] }}" alt="Thumbnail" class="object-cover w-full h-full" loading="lazy" />
                            </button>
                            <button wire:click="deleteWallpaper('{{ $wallpaper['id'] }}')"
                                    class="absolute -top-1.5 -right-1.5 btn btn-error btn-circle btn-xs opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <x-icon name="lucide.trash" class="w-3 h-3" />
                            </button>
                        </div>
                    @endforeach

                    @foreach($pendingJobs as $jobId)
                        <button wire:click="selectPendingJob" wire:key="pending-{{ $jobId }}"
                                class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all
                                       {{ $showLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }} skeleton">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- Desktop layout: mockup + horizontal thumbnails below --}}
        <div class="flex flex-col items-center gap-3 w-full">
            {{-- Desktop mockup --}}
            <x-mockup-monitor>
                <div class="relative bg-linear-to-tr from-slate-700 to-gray-900 flex items-center justify-center aspect-video">
                    @if($showLoading && count($pendingJobs) > 0)
                        <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-linear-to-tr from-slate-700 to-gray-900">
                            <span class="loading loading-spinner loading-lg text-primary"></span>
                            <span class="text-sm text-base-content/70 animate-pulse">{{ $loadingPhrase }}</span>
                        </div>
                    @endif
                    @if($activeWallpaper && !$showLoading)
                        <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                             src="{{ $activeWallpaper['url'] }}" />
                        <x-button wire:click="downloadImage"
                                  class="absolute bottom-6 right-4 btn-accent btn-circle" icon="c-arrow-down-tray" spinner />
                    @elseif(!$showLoading || count($pendingJobs) === 0)
                        <div class="opacity-20">
                            <livewire:logo />
                        </div>
                    @endif
                    <div class="absolute z-11 bottom-6 left-4">
                        {{-- Horizontal thumbnails below --}}
                        @if(count($wallpapers) > 0 || count($pendingJobs) > 0)
                            <div class="flex flex-row gap-2 overflow-x-auto overflow-x-hidden max-w-full py-2 px-2">
                                @foreach($wallpapers as $index => $wallpaper)
                                    <div class="relative shrink-0 group" wire:key="thumb-{{ $wallpaper['id'] }}">
                                        <button wire:click="selectWallpaper({{ $index }})"
                                                class="w-16 h-16 rounded-lg overflow-hidden border-2 transition-all hover:scale-105
                                           {{ $activeWallpaper && $activeWallpaper['id'] === $wallpaper['id'] && !$showLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }}">
                                            <img src="{{ $wallpaper['url'] }}" alt="Thumbnail" class="object-cover w-full h-full" loading="lazy" />
                                        </button>
                                        <button wire:click="deleteWallpaper('{{ $wallpaper['id'] }}')"
                                                class="absolute -top-1.5 -right-1.5 btn btn-error btn-circle btn-xs opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            <x-icon name="lucide.trash" class="w-3 h-3" />
                                        </button>
                                    </div>
                                @endforeach

                                @foreach($pendingJobs as $jobId)
                                    <button wire:click="selectPendingJob" wire:key="pending-{{ $jobId }}"
                                            class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all
                                       {{ $showLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }} skeleton">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </x-mockup-monitor>
        </div>
    @endif

    {{-- Set as Wallpaper instructions modal (mobile only) --}}
    <dialog id="wallpaper-instructions-modal" class="modal modal-bottom md:hidden">
        <div class="modal-box">
            <h3 class="text-lg font-bold">Set as Wallpaper</h3>
            <p class="py-2 text-base-content/70">Your wallpaper has been downloaded!</p>
            <ol class="list-decimal list-inside space-y-2 text-sm">
                <li>Open your <strong>Photos</strong> or <strong>Gallery</strong> app</li>
                <li>Find the downloaded image</li>
                <li>Tap <strong>Share</strong> or the <strong>⋮ Menu</strong></li>
                <li>Select <strong>"Set as Wallpaper"</strong></li>
            </ol>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-primary">Got it</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>
