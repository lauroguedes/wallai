<?php

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $prompt = '';

    public string $selectedStyle = BackgroundStyle::NaturalLandscape->value;

    public string $deviceType = DeviceType::Mobile->value;

    public bool $showDrawer = false;

    public function selectStyle(string $style): void
    {
        $this->selectedStyle = $style;
        $this->showDrawer = false;
    }

    #[On('device-type-changed')]
    public function onDeviceTypeChanged(string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    public function generate(WallpaperService $service): void
    {
        try {
            $style = BackgroundStyle::from($this->selectedStyle);
            $deviceType = DeviceType::from($this->deviceType);
            $wallpaperData = $service->generateImage($this->prompt, $style, $deviceType);

            $this->dispatch('wallpaper-generated', $wallpaperData);
        } catch (ServiceGeneratorException $e) {
            report($e);
            $this->error($e->getUserMessage());
        }
    }

    public function generatePrompt(WallpaperService $service): void
    {
        try {
            $style = BackgroundStyle::from($this->selectedStyle);
            $deviceType = DeviceType::from($this->deviceType);
            $this->prompt = $service->generatePrompt($style, $deviceType);
        } catch (ServiceGeneratorException $e) {
            report($e);
            $this->error($e->getUserMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-2">
    <livewire:logo/>
    <x-form class="mt-5" wire:submit="generate" x-on:submit="$dispatch('generating')">
        <div>
            <label class="label label-text font-semibold mb-1">Style</label>
            <x-style-selected-card
                :style="BackgroundStyle::from($selectedStyle)"
                wire:click="$toggle('showDrawer')" />
        </div>

        <x-textarea
            class="w-72"
            wire:model="prompt"
            placeholder="Write your prompt here..."
            rows="3"
            inline />

        <div class="flex justify-between items-center">
            <x-button wire:click="generatePrompt" icon="lucide.dices" class="btn-square"
                      spinner="generatePrompt" />
            <x-button class="btn-secondary" type="submit" spinner="generate">Generate</x-button>
        </div>
    </x-form>

    <x-drawer wire:model="showDrawer" title="Choose a Style" right withCloseButton closeOnEscape class="w-11/12 lg:w-1/3">
        <div class="grid grid-cols-2 gap-3">
            @foreach(BackgroundStyle::cases() as $style)
                <x-style-picker-card
                    :style="$style"
                    :selected="$style->value === $selectedStyle"
                    wire:click="selectStyle('{{ $style->value }}')" />
            @endforeach
        </div>
    </x-drawer>
</div>
