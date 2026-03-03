<?php

use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    use Toast;

    public array $wallpapers = ['mobile' => null, 'desktop' => null];

    public string $deviceType = 'mobile';

    #[On('wallpaper-generated')]
    public function onWallpaperGenerated(array $wallpaper): void
    {
        $this->wallpapers[$this->deviceType] = $wallpaper;
    }

    public function setDeviceType(string $type): void
    {
        $this->deviceType = $type;
        $this->dispatch('device-type-changed', $type);
    }

    public function downloadImage(): ?StreamedResponse
    {
        try {
            $wallpaper = $this->wallpapers[$this->deviceType];
            $path = $wallpaper['path'];
            $extension = $wallpaper['extension'];

            $content = Storage::disk('public')->get($path);
            $prefix = DeviceType::from($this->deviceType)->filenamePrefix();

            Storage::disk('public')->delete($path);
            $this->wallpapers[$this->deviceType] = null;

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $prefix.'.'.$extension);
        } catch (\Throwable $e) {
            $exception = ServiceGeneratorException::downloadFailed($e, [
                'wallpaper' => $this->wallpapers[$this->deviceType],
            ]);

            report($exception);
            $this->error($exception->getUserMessage());

            return null;
        }
    }
}; ?>

<div class="flex flex-col items-center justify-center gap-4 w-full h-full"
     x-data="{ generating: false }"
     @generating.window="generating = true"
     @wallpaper-generated.window="generating = false">
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
                    <div x-show="generating" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-linear-to-tr from-slate-700 to-gray-900">
                        <span class="skeleton skeleton-text">AI is thinking harder...</span>
                    </div>
                    @if($wallpapers['mobile'])
                        <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                             src="{{ $wallpapers['mobile']['url'] }}" />
                        <x-button wire:click="downloadImage" class="absolute bottom-6 right-4 btn-accent btn-circle"
                                  icon="c-arrow-down-tray" spinner />
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
                <div x-show="generating" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-linear-to-tr from-slate-700 to-gray-900">
                    <span class="skeleton skeleton-text">AI is thinking harder...</span>
                </div>
                @if($wallpapers['desktop'])
                    <img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper"
                         src="{{ $wallpapers['desktop']['url'] }}" />
                    <x-button wire:click="downloadImage" class="absolute bottom-6 right-4 btn-accent btn-circle"
                              icon="c-arrow-down-tray" spinner />
                @else
                    <div class="opacity-20">
                        <livewire:logo />
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
