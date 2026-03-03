<?php

use App\Enums\DeviceType;
use App\Enums\ImageType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $prompt = '';

    public string $selectedOption = ImageType::Realistic->value;

    public string $deviceType = DeviceType::Mobile->value;

    public array $options = [
        ['id' => ImageType::Realistic->value, 'name' => ImageType::Realistic->name],
        ['id' => ImageType::Artistic->value, 'name' => ImageType::Artistic->name],
        ['id' => ImageType::Abstract->value, 'name' => ImageType::Abstract->name],
    ];

    #[On('device-type-changed')]
    public function onDeviceTypeChanged(string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    public function generate(WallpaperService $service): void
    {
        try {
            $deviceType = DeviceType::from($this->deviceType);
            $wallpaperData = $service->generateImage($this->prompt, $this->selectedOption, $deviceType);

            $this->dispatch('wallpaper-generated', $wallpaperData);
        } catch (ServiceGeneratorException $e) {
            report($e);
            $this->error($e->getUserMessage());
        }
    }

    public function generatePrompt(WallpaperService $service): void
    {
        try {
            $deviceType = DeviceType::from($this->deviceType);
            $this->prompt = $service->generatePrompt($this->selectedOption, $deviceType);
        } catch (ServiceGeneratorException $e) {
            report($e);
            $this->error($e->getUserMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-2">
    <livewire:logo/>
    <x-form class="mt-5" wire:submit="generate" x-on:submit="$dispatch('generating')">
        <x-group
            label="Style"
            :options="$options"
            wire:model="selectedOption" />

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
</div>
