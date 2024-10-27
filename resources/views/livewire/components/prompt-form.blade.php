<?php

use App\Enums\ImageType;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Component;

new class extends Component {
    public string $prompt = '';

    public string $selectedOption = ImageType::Realistic->value;

    public array $options = [
        ['id' => ImageType::Realistic->value, 'name' => ImageType::Realistic->name],
        ['id' => ImageType::Artistic->value, 'name' => ImageType::Artistic->name],
        ['id' => ImageType::Abstract->value, 'name' => ImageType::Abstract->name],
    ];

    public function generate(): void
    {
        try {
            $wallpaperData = $this->generateWallpaper();

            $this->dispatch('wallpaper-generated', $wallpaperData);
        } catch (Throwable $e) {
            dd($e->getMessage());
        }
    }

    private function generateWallpaper(): array
    {
        return Http::get('https://api.unsplash.com/photos/random', [
            'orientation' => 'portrait',
            'client_id' => config('services.unsplash.key'),
            'content_filter' => 'high',
            'topics' => implode(',', ['iPhone Wallpaper', $this->selectedOption, $this->prompt]),
        ])
            ->throw()
            ->json();
    }
}; ?>

<div class="flex flex-col gap-2">
    <x-form wire:submit="generate">
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
            <x-button icon="c-arrow-path-rounded-square" class="btn-square"/>
            <x-button class="btn-secondary" type="submit" spinner="generate">Generate</x-button>
        </div>
    </x-form>
</div>