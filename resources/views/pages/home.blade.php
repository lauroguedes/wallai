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
        <div class="w-[70%] flex justify-center">
            <livewire:preview />
        </div>
    </div>
</div>
