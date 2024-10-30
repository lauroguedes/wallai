<?php

use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    public ?array $wallpaper = null;

    #[On('wallpaper-generated')]
    public function onWallpaperGenerated(array $wallpaper): void
    {
        $this->wallpaper = $wallpaper;
    }

    public function downloadImage(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo file_get_contents($this->wallpaper['url']);
        }, 'phone_wallpaper.' . config('services.replicate.output_format'));
    }
}; ?>

<div>
    <div class="mockup-phone border-primary">
        <div class="camera"></div>
        <div class="display">
            <div class="artboard artboard-demo w-[320px] h-[700px] bg-gradient-to-tr from-slate-700 to-gray-900">
                <div class="relative">
                    @if($wallpaper)
                        <x-button wire:click="downloadImage" class="absolute right-5 bottom-16 btn-accent"
                                  icon="c-arrow-down-tray" spinner/>
                        <img wire:replace loading="lazy" class="object-cover object-center h-screen" alt="Wallpaper"
                             src="{{ $wallpaper['url'] }}">
                    @else
                        <div class="opacity-20">
                            <livewire:components.logo/>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
