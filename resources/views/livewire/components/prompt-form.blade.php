<?php

use App\Enums\ImageType;
use App\Services\AbstractImageGenerator;
use App\Services\AbstractTextGenerator;
use Livewire\Volt\Component;
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

    public function generate(AbstractImageGenerator $client): void
    {
        try {
            $wallpaperData = $client->generate($this->prompt, $this->selectedOption);

            $this->dispatch('wallpaper-generated', $wallpaperData);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function generatePrompt(AbstractTextGenerator $client): void
    {
        try {
            $prompt = $client->generate($this->selectedOption);
            $this->prompt = trim(implode('', $prompt));
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-2">
    <livewire:components.logo/>
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
            <x-button wire:click="generatePrompt" icon="c-arrow-path-rounded-square" class="btn-square"
                      spinner="generatePrompt"/>
            <x-button class="btn-secondary" type="submit" spinner="generate">Generate</x-button>
        </div>
    </x-form>
</div>
