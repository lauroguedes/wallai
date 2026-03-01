<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<div class="flex flex-col justify-center items-center">
    <x-toast />
    <div class="w-1/2 flex h-screen justify-center items-center gap-8">
        <livewire:prompt-form />
        <livewire:preview />
    </div>
</div>
