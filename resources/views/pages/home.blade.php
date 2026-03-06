<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<div class="flex flex-col justify-center items-center">
    <x-toast />
    <div class="flex h-screen items-center gap-8 w-full px-8">
        <div class="w-[30%] shrink-0 flex justify-center">
            <livewire:prompt-form />
        </div>
        <div class="divider divider-horizontal"></div>
        <div x-data="{ deviceType: 'mobile' }" class="w-[70%] flex flex-col items-center gap-4 h-full justify-center">
            {{-- Device type toggle --}}
            <div class="join">
                <button @click="deviceType = 'mobile'; $dispatch('device-type-set', { type: 'mobile' })"
                        :class="deviceType === 'mobile' ? 'btn-active' : ''"
                        class="btn join-item">
                    <x-icon name="lucide.smartphone" class="w-5 h-5" />
                </button>
                <button @click="deviceType = 'desktop'; $dispatch('device-type-set', { type: 'desktop' })"
                        :class="deviceType === 'desktop' ? 'btn-active' : ''"
                        class="btn join-item">
                    <x-icon name="lucide.monitor" class="w-5 h-5" />
                </button>
            </div>

            {{-- Two isolated preview instances --}}
            <div x-show="deviceType === 'mobile'" class="w-full flex justify-center">
                <livewire:preview device-type="mobile" wire:key="preview-mobile" />
            </div>
            <div x-show="deviceType === 'desktop'" x-cloak class="w-full flex justify-center">
                <livewire:preview device-type="desktop" wire:key="preview-desktop" />
            </div>
        </div>
    </div>
</div>
