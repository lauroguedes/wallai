<?php

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Exceptions\ServiceGeneratorException;
use App\Services\WallpaperService;
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

    public function generate(WallpaperService $service): void
    {
        $sessionId = session()->getId();

        if ($service->getPendingJobCount($sessionId) >= WallpaperService::maxPendingJobs()) {
            $this->error('You have too many pending generations. Please wait.');

            return;
        }

        $style = BackgroundStyle::from($this->selectedStyle);
        $deviceType = DeviceType::from($this->deviceType);

        $jobId = $service->dispatchGeneration($sessionId, $this->prompt, $style, $deviceType);

        $this->dispatch('wallpaper-job-dispatched', jobId: $jobId, deviceType: $this->deviceType);
        $this->success('Your wallpaper is being generated!');
    }

    public function generatePrompt(WallpaperService $service): void
    {
        try {
            $style = BackgroundStyle::from($this->selectedStyle);
            $deviceType = DeviceType::from($this->deviceType);
            $this->prompt = $service->generatePrompt($style, $deviceType, $this->prompt);
        } catch (ServiceGeneratorException $e) {
            report($e);
            $this->error($e->getUserMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-2"
     x-on:device-type-set.window="$wire.set('deviceType', $event.detail.type)">
    <x-logo />
    <x-form class="mt-5 w-full min-w-xs max-w-xs" wire:submit="generate">
        <div>
            <label class="label label-text font-semibold mb-1">Style</label>
            <x-style-selected-card
                :style="BackgroundStyle::from($selectedStyle)"
                wire:click="$toggle('showDrawer')" />
        </div>

        <x-textarea
            class="w-full rounded-2xl"
            wire:model="prompt"
            placeholder="Write your prompt here..."
            rows="8"
            inline />

        <div class="flex justify-between items-center">
            <x-button wire:click="generatePrompt" tooltip-right="Generate Prompt" icon="lucide.dices" class="btn-square btn-soft rounded-2xl"
                      spinner="generatePrompt" />
            <x-button class="btn-secondary rounded-2xl" icon="lucide.sparkles" type="submit" spinner="generate">Generate</x-button>
        </div>
    </x-form>

    @teleport('body')
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
    @endteleport
</div>
