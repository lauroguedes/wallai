<?php

use App\Enums\GenerationProvider;
use App\Services\AiProviderSettings;
use App\Support\AiModelCatalog;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $showDrawer = false;

    public string $textProvider = GenerationProvider::Gemini->value;

    public string $textModel = 'gemini-3.7-flash';

    public string $imageProvider = GenerationProvider::Gemini->value;

    public string $imageModel = 'gemini-3.1-flash-image-preview';

    public string $openAiApiKey = '';

    public string $geminiApiKey = '';

    public string $openAiKeyStatus = 'No key configured';

    public string $geminiKeyStatus = 'No key configured';

    public bool $hasStoredOpenAiKey = false;

    public bool $hasStoredGeminiKey = false;

    public function mount(AiProviderSettings $settings): void
    {
        $current = $settings->current();

        $this->textProvider = $settings->textProvider($current)->value;
        $this->textModel = $settings->textModel($current);
        $this->imageProvider = $settings->imageProvider($current)->value;
        $this->imageModel = $settings->imageModel($current);
        $this->refreshKeyStatuses($settings);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function providerOptions(): array
    {
        return GenerationProvider::options();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function textModelOptions(): array
    {
        $provider = GenerationProvider::tryFrom($this->textProvider);

        return $provider === null
            ? []
            : app(AiModelCatalog::class)->options($provider, AiModelCatalog::TEXT);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function imageModelOptions(): array
    {
        $provider = GenerationProvider::tryFrom($this->imageProvider);

        return $provider === null
            ? []
            : app(AiModelCatalog::class)->options($provider, AiModelCatalog::IMAGE);
    }

    public function updatedTextProvider(): void
    {
        $provider = GenerationProvider::tryFrom($this->textProvider);

        if ($provider !== null) {
            $this->textModel = app(AiModelCatalog::class)->default($provider, AiModelCatalog::TEXT);
        }
    }

    public function updatedImageProvider(): void
    {
        $provider = GenerationProvider::tryFrom($this->imageProvider);

        if ($provider !== null) {
            $this->imageModel = app(AiModelCatalog::class)->default($provider, AiModelCatalog::IMAGE);
        }
    }

    #[On('open-provider-settings')]
    public function openDrawer(): void
    {
        $this->showDrawer = true;
    }

    public function save(AiProviderSettings $settings, AiModelCatalog $models): void
    {
        $selectedTextProvider = GenerationProvider::tryFrom($this->textProvider);
        $selectedImageProvider = GenerationProvider::tryFrom($this->imageProvider);

        $validated = $this->validate([
            'textProvider' => ['required', Rule::enum(GenerationProvider::class)],
            'textModel' => ['required', 'string', Rule::in(
                $selectedTextProvider === null ? [] : $models->ids($selectedTextProvider, AiModelCatalog::TEXT),
            )],
            'imageProvider' => ['required', Rule::enum(GenerationProvider::class)],
            'imageModel' => ['required', 'string', Rule::in(
                $selectedImageProvider === null ? [] : $models->ids($selectedImageProvider, AiModelCatalog::IMAGE),
            )],
            'openAiApiKey' => ['nullable', 'string', 'max:512'],
            'geminiApiKey' => ['nullable', 'string', 'max:512'],
        ]);

        $textProvider = GenerationProvider::from($validated['textProvider']);
        $imageProvider = GenerationProvider::from($validated['imageProvider']);
        $current = $settings->current();

        foreach (array_unique([$textProvider->value, $imageProvider->value]) as $providerValue) {
            $provider = GenerationProvider::from($providerValue);
            $newKey = $provider === GenerationProvider::OpenAI
                ? $validated['openAiApiKey']
                : $validated['geminiApiKey'];

            if (blank($newKey) && $settings->effectiveKey($provider, $current) === null) {
                $this->addError(
                    $provider === GenerationProvider::OpenAI ? 'openAiApiKey' : 'geminiApiKey',
                    "An API key is required for {$provider->label()}.",
                );
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $settings->save(
            $textProvider,
            $validated['textModel'],
            $imageProvider,
            $validated['imageModel'],
            $validated['openAiApiKey'],
            $validated['geminiApiKey'],
        );

        $this->reset('openAiApiKey', 'geminiApiKey');
        $this->refreshKeyStatuses($settings);
        $this->showDrawer = false;
        $this->success('AI provider settings saved.');
    }

    public function removeKey(string $provider, AiProviderSettings $settings): void
    {
        $generationProvider = GenerationProvider::tryFrom($provider);

        if ($generationProvider === null) {
            throw ValidationException::withMessages([
                'provider' => 'The selected provider is invalid.',
            ]);
        }

        $settings->removeKey($generationProvider);
        $this->refreshKeyStatuses($settings);
        $this->success("Saved {$generationProvider->label()} key removed.");
    }

    public function forget(AiProviderSettings $settings): void
    {
        $settings->forget();

        $this->textProvider = GenerationProvider::from(
            (string) config('wallpaper.ai.text_provider', GenerationProvider::Gemini->value),
        )->value;
        $this->imageProvider = GenerationProvider::from(
            (string) config('wallpaper.ai.image_provider', GenerationProvider::Gemini->value),
        )->value;
        $this->textModel = app(AiModelCatalog::class)->default(
            GenerationProvider::from($this->textProvider),
            AiModelCatalog::TEXT,
        );
        $this->imageModel = app(AiModelCatalog::class)->default(
            GenerationProvider::from($this->imageProvider),
            AiModelCatalog::IMAGE,
        );
        $this->reset('openAiApiKey', 'geminiApiKey');
        $this->refreshKeyStatuses($settings);
        $this->success('Session provider settings removed.');
    }

    private function refreshKeyStatuses(AiProviderSettings $settings): void
    {
        $current = $settings->current();

        $this->openAiKeyStatus = $settings->keyStatus(GenerationProvider::OpenAI, $current);
        $this->geminiKeyStatus = $settings->keyStatus(GenerationProvider::Gemini, $current);
        $this->hasStoredOpenAiKey = $settings->hasStoredKey(GenerationProvider::OpenAI, $current);
        $this->hasStoredGeminiKey = $settings->hasStoredKey(GenerationProvider::Gemini, $current);
    }
};
?>

<div>
    <x-button
        wire:click="$toggle('showDrawer')"
        icon="lucide.settings"
        class="btn-circle btn-soft fixed right-5 top-5 z-30 border border-base-200 bg-base-100/60 backdrop-blur-md"
        tooltip-left="AI provider settings"
        aria-label="AI provider settings" />

    @teleport('body')
        <x-drawer
            wire:model="showDrawer"
            title="AI Provider Settings"
            subtitle="Choose which services create prompts and images."
            right
            withCloseButton
            closeOnEscape
            class="w-11/12 md:w-[30rem]">
            <x-form wire:submit="save" class="flex flex-col gap-6">
                <x-alert
                    icon="lucide.shield-check"
                    class="alert-info"
                    title="Your keys stay on this server"
                    description="Keys are encrypted before storage and are never returned to this form after saving." />

                <section class="flex flex-col gap-4">
                    <div>
                        <h3 class="font-semibold">Provider selection</h3>
                        <p class="text-sm text-base-content/60">Text powers prompt generation and prompt engineering. Image creates the final wallpaper.</p>
                    </div>

                    <x-select
                        label="Text provider"
                        icon="lucide.message-square-text"
                        :options="$this->providerOptions()"
                        wire:model.live="textProvider" />

                    <x-select
                        label="Text model"
                        icon="lucide.cpu"
                        :options="$this->textModelOptions()"
                        wire:model="textModel"
                        wire:key="text-model-{{ $textProvider }}" />

                    <x-select
                        label="Image provider"
                        icon="lucide.image"
                        :options="$this->providerOptions()"
                        wire:model.live="imageProvider" />

                    <x-select
                        label="Image model"
                        icon="lucide.scan"
                        :options="$this->imageModelOptions()"
                        wire:model="imageModel"
                        wire:key="image-model-{{ $imageProvider }}" />
                </section>

                <div class="divider my-0">Credentials</div>

                @if($textProvider === GenerationProvider::OpenAI->value || $imageProvider === GenerationProvider::OpenAI->value)
                    <section class="flex flex-col gap-3" wire:key="openai-credentials">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">OpenAI</h3>
                                <p class="text-xs text-base-content/60">{{ $openAiKeyStatus }}</p>
                            </div>
                            @if($hasStoredOpenAiKey)
                                <x-button
                                    type="button"
                                    wire:click="removeKey('openai')"
                                    wire:confirm="Remove the saved OpenAI key?"
                                    icon="lucide.trash-2"
                                    class="btn-ghost btn-sm text-error"
                                    label="Remove" />
                            @endif
                        </div>

                        <x-password
                            label="OpenAI API key"
                            wire:model="openAiApiKey"
                            placeholder="Leave blank to keep the current key"
                            autocomplete="new-password"
                            right />
                    </section>
                @endif

                @if($textProvider === GenerationProvider::Gemini->value || $imageProvider === GenerationProvider::Gemini->value)
                    <section class="flex flex-col gap-3" wire:key="gemini-credentials">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">Google Gemini</h3>
                                <p class="text-xs text-base-content/60">{{ $geminiKeyStatus }}</p>
                            </div>
                            @if($hasStoredGeminiKey)
                                <x-button
                                    type="button"
                                    wire:click="removeKey('gemini')"
                                    wire:confirm="Remove the saved Gemini key?"
                                    icon="lucide.trash-2"
                                    class="btn-ghost btn-sm text-error"
                                    label="Remove" />
                            @endif
                        </div>

                        <x-password
                            label="Gemini API key"
                            wire:model="geminiApiKey"
                            placeholder="Leave blank to keep the current key"
                            autocomplete="new-password"
                            right />
                    </section>
                @endif

                <x-slot:actions>
                    <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                        <x-button
                            type="button"
                            wire:click="forget"
                            wire:confirm="Forget all provider choices and saved keys for this session?"
                            icon="lucide.rotate-ccw"
                            class="btn-ghost text-error"
                            label="Reset session" />
                        <x-button
                            type="submit"
                            spinner="save"
                            icon="lucide.save"
                            class="btn-primary"
                            label="Save settings" />
                    </div>
                </x-slot:actions>
            </x-form>
        </x-drawer>
    @endteleport
</div>
