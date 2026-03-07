<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<div class="flex flex-col justify-center items-center">
    <x-toast />
    <div class="flex h-screen items-center w-full">
        <div class="w-full h-full border-r border-neutral max-w-sm shrink-0 flex justify-center items-center left-side-bg">
            <livewire:prompt-form />
        </div>
        <div x-data="{ deviceType: 'mobile' }" class="w-full flex flex-col items-center gap-4 h-full justify-center right-panel">
            {{-- Device type toggle --}}
            <div class="join fixed top-3 rounded-2xl border border-base-200 p-1 backdrop-blur-md bg-base-100/50">
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

            {{-- Two isolated preview instances --}}
            <div x-show="deviceType === 'mobile'" class="w-full flex justify-center mt-3">
                <livewire:preview device-type="mobile" wire:key="preview-mobile" />
            </div>
            <div x-show="deviceType === 'desktop'" x-cloak class="w-full flex justify-center px-8">
                <livewire:preview device-type="desktop" wire:key="preview-desktop" />
            </div>
        </div>
    </div>
</div>
