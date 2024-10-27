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

    public function getImageUrl(): string
    {
        return $this->wallpaper['urls']['regular'] ?? asset('wallpapers/fog_lake.jpeg');
    }

    public function downloadImage(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo file_get_contents($this->wallpaper['urls']['full']);
        }, 'phone_wallpaper.jpg');
    }
}; ?>

<div>
{{--    <div--}}
{{--        class="w-72 relative flex flex-col items-center aspect-[9/18] bg-gray-200 rounded-3xl border-8 border-base-300 shadow-lg overflow-hidden">--}}
{{--        <div class="rounded-full absolute z-10 bg-base-300 w-3/6 h-9 -top-5"></div>--}}
{{--        <div class="h-full relative">--}}
{{--            @if($wallpaper)--}}
{{--                <x-button wire:click="downloadImage" class="absolute right-2 bottom-2 btn-accent"--}}
{{--                          icon="c-arrow-down-tray" spinner/>--}}
{{--            @endif--}}
{{--            <img loading="lazy" class="object-cover object-center h-screen" alt="Wallpaper"--}}
{{--                 src="{{ $this->getImageUrl() }}">--}}
{{--        </div>--}}
{{--    </div>--}}
    <div class="mockup-phone">
        <div class="camera"></div>
        <div class="display">
            <div class="artboard artboard-demo phone-1">
                <div class="h-full relative">
                    @if($wallpaper)
                        <x-button wire:click="downloadImage" class="absolute right-5 bottom-5 btn-accent"
                                  icon="c-arrow-down-tray" spinner/>
                    @endif
                    <img loading="lazy" class="object-cover object-center h-screen" alt="Wallpaper"
                         src="{{ $this->getImageUrl() }}">
                </div>
            </div>
        </div>
    </div>
</div>
