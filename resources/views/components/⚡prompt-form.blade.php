<?php

use App\Enums\ImageType;
use App\Services\WallpaperService;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $prompt = '';

    public string $selectedOption = ImageType::Realistic->value;

    public array $options = [
        ['id' => ImageType::Realistic->value, 'name' => ImageType::Realistic->name],
        ['id' => ImageType::Artistic->value, 'name' => ImageType::Artistic->name],
        ['id' => ImageType::Abstract->value, 'name' => ImageType::Abstract->name],
    ];

    public function generate(WallpaperService $service): void
    {
        try {
            $wallpaperData = $service->generateImage($this->prompt, $this->selectedOption);

            $this->dispatch('wallpaper-generated', $wallpaperData);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function generatePrompt(WallpaperService $service): void
    {
        try {
            $this->prompt = $service->generatePrompt($this->selectedOption);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-2">
    <livewire:logo/>
    <x-form class="mt-5" wire:submit="generate">
        <x-radio
            label="Select one"
            :options="$options"
            wire:model="selectedOption"/>

        <x-textarea
            class="w-72"
            wire:model="prompt"
            placeholder="Write your prompt here..."
            rows="3"
            inline/>
        <div class="flex justify-between items-center">
            <x-button wire:click="generatePrompt" icon="lucide.dices" class="btn-square"
                      spinner="generatePrompt"/>
            <x-button class="btn-secondary" type="submit" spinner="generate">Generate</x-button>
        </div>
    </x-form>
</div>
