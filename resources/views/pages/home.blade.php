<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<div class="flex flex-col justify-center items-center">
    <x-toast />
    <div class="drawer lg:drawer-open h-screen">
        <input id="settings-drawer" type="checkbox" class="drawer-toggle" />

        {{-- Main content area --}}
        <div class="drawer-content flex h-screen items-center w-full">
            <div x-data="{ deviceType: 'mobile', wallpaperBgUrls: { mobile: null, desktop: null } }"
                 x-on:wallpaper-bg-updated.window="wallpaperBgUrls[$event.detail.deviceType] = $event.detail.url"
                 class="w-full flex flex-col items-center gap-4 h-full justify-center relative overflow-hidden">

                {{-- Frosted glass background effect (tablet/desktop only) --}}
                <img x-show="wallpaperBgUrls[deviceType]" :src="wallpaperBgUrls[deviceType]"
                     x-transition:enter="transition-opacity duration-700"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-30"
                     class="hidden md:block absolute inset-0 w-full h-full object-cover blur-3xl opacity-30 scale-110 pointer-events-none z-0"
                     alt="" />
                <div class="hidden md:block absolute inset-0 right-panel-gradient pointer-events-none z-[1]"></div>

                {{-- Top bar: settings button + device toggle --}}
                <div class="fixed top-3 z-30 flex items-center gap-2">
                    {{-- Settings icon (tablet & mobile only) --}}
                    <label for="settings-drawer" class="btn btn-circle btn-ghost lg:hidden backdrop-blur-md bg-base-100/50">
                        <x-icon name="lucide.settings" class="w-5 h-5" />
                    </label>

                    {{-- Device toggle (hidden on mobile, visible on md+) --}}
                    <div class="join hidden md:flex rounded-2xl border border-base-200 p-1 backdrop-blur-md bg-base-100/50">
                        <button @click="deviceType = 'mobile'; $dispatch('device-type-set', { type: 'mobile' })"
                                :class="deviceType === 'mobile' ? 'btn-active' : ''"
                                class="btn btn-soft rounded-s-2xl join-item">
                            <x-icon name="lucide.smartphone" class="w-5 h-5" />
                        </button>
                        <button @click="deviceType = 'desktop'; $dispatch('device-type-set', { type: 'desktop' })"
                                :class="deviceType === 'desktop' ? 'btn-active' : ''"
                                class="btn btn-soft rounded-e-2xl join-item">
                            <x-icon name="lucide.monitor" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- Preview instances --}}
                <div x-show="deviceType === 'mobile'" class="w-full flex justify-center md:mt-3 h-full md:h-auto relative z-10">
                    <livewire:preview device-type="mobile" wire:key="preview-mobile" />
                </div>
                <div x-show="deviceType === 'desktop'" x-cloak class="w-full justify-center px-8 hidden md:flex relative z-10">
                    <livewire:preview device-type="desktop" wire:key="preview-desktop" />
                </div>
            </div>
        </div>

        {{-- Drawer sidebar --}}
        <div class="drawer-side z-40">
            <label for="settings-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="w-full md:px-7 max-w-sm min-h-full border-r border-neutral left-side-bg flex justify-center items-center">
                <livewire:prompt-form />
            </div>
        </div>
    </div>
</div>
