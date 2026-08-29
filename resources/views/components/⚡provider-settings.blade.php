<?php

use App\Enums\GenerationProvider;
use App\Services\AiProviderSettings;
use App\Services\ApplicationSetup;
use App\Services\WallpaperService;
use App\Services\WorkspaceContext;
use App\Support\AiModelCatalog;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $showDrawer = false;

    public bool $showResetModal = false;

    public bool $showRemoveKeyModal = false;

    public ?string $pendingKeyProvider = null;

    public string $textProvider = GenerationProvider::Gemini->value;

    public string $textModel = '';

    public string $imageProvider = GenerationProvider::Gemini->value;

    public string $imageModel = '';

    public string $openAiApiKey = '';

    public string $geminiApiKey = '';

    public string $ollamaUrl = 'http://localhost:11434';

    public string $openAiKeyStatus = 'No key configured';

    public string $geminiKeyStatus = 'No key configured';

    public bool $hasStoredOpenAiKey = false;

    public bool $hasStoredGeminiKey = false;

    public bool $authenticationEnabled = false;

    public string $selectedTab = 'providers-tab';

    public function mount(AiProviderSettings $settings, ApplicationSetup $setup): void
    {
        $current = $settings->current();

        $this->textProvider = $settings->textProvider($current)->value;
        $this->textModel = $settings->textModel($current);
        $this->imageProvider = $settings->imageProvider($current)->value;
        $this->imageModel = $settings->imageModel($current);
        $this->ollamaUrl = $settings->ollamaUrl($current);
        $this->authenticationEnabled = $setup->authenticationEnabled();
        $this->refreshKeyStatuses($settings);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function textProviderOptions(): array
    {
        return GenerationProvider::options(GenerationProvider::TEXT);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function imageProviderOptions(): array
    {
        return GenerationProvider::options(GenerationProvider::IMAGE);
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

    public function textModelIsCustom(): bool
    {
        $provider = GenerationProvider::tryFrom($this->textProvider);

        return $provider !== null
            && app(AiModelCatalog::class)->allowsCustomModel($provider, AiModelCatalog::TEXT);
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
        $this->selectedTab = 'providers-tab';
        $this->showDrawer = true;
    }

    public function save(AiProviderSettings $settings, AiModelCatalog $models): void
    {
        $selectedTextProvider = GenerationProvider::tryFrom($this->textProvider);
        $selectedImageProvider = GenerationProvider::tryFrom($this->imageProvider);
        $textModelRules = ['required', 'string', 'max:255'];

        if ($selectedTextProvider !== null && ! $models->allowsCustomModel($selectedTextProvider, AiModelCatalog::TEXT)) {
            $textModelRules[] = Rule::in($models->ids($selectedTextProvider, AiModelCatalog::TEXT));
        }

        $validated = $this->validate([
            'textProvider' => [
                'required',
                Rule::enum(GenerationProvider::class)->only(
                    array_filter(GenerationProvider::cases(), fn (GenerationProvider $provider): bool => $provider->supports(GenerationProvider::TEXT)),
                ),
            ],
            'textModel' => $textModelRules,
            'imageProvider' => [
                'required',
                Rule::enum(GenerationProvider::class)->only(
                    array_filter(GenerationProvider::cases(), fn (GenerationProvider $provider): bool => $provider->supports(GenerationProvider::IMAGE)),
                ),
            ],
            'imageModel' => ['required', 'string', Rule::in(
                $selectedImageProvider === null ? [] : $models->ids($selectedImageProvider, AiModelCatalog::IMAGE),
            )],
            'openAiApiKey' => ['nullable', 'string', 'max:512'],
            'geminiApiKey' => ['nullable', 'string', 'max:512'],
            'ollamaUrl' => [
                Rule::requiredIf($selectedTextProvider === GenerationProvider::Ollama),
                'nullable',
                'string',
                'url:http,https',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail) use ($settings): void {
                    if (filled($value) && ! $settings->isOllamaHostAllowed((string) $value)) {
                        $fail('This Ollama server host is not allowed by the installation administrator.');
                    }
                },
            ],
        ]);

        $textProvider = GenerationProvider::from($validated['textProvider']);
        $imageProvider = GenerationProvider::from($validated['imageProvider']);
        $current = $settings->current();

        foreach (array_unique([$textProvider->value, $imageProvider->value]) as $providerValue) {
            $provider = GenerationProvider::from($providerValue);

            if (! $provider->requiresApiKey()) {
                continue;
            }

            $newKey = match ($provider) {
                GenerationProvider::OpenAI => $validated['openAiApiKey'],
                GenerationProvider::Gemini => $validated['geminiApiKey'],
                GenerationProvider::Ollama => null,
            };

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
            $validated['ollamaUrl'],
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

    public function requestKeyRemoval(string $provider, AiProviderSettings $settings): void
    {
        $generationProvider = GenerationProvider::tryFrom($provider);

        if ($generationProvider === null || ! $settings->hasStoredKey($generationProvider, $settings->current())) {
            throw ValidationException::withMessages([
                'provider' => 'The selected provider does not have a saved key.',
            ]);
        }

        $this->pendingKeyProvider = $generationProvider->value;
        $this->showRemoveKeyModal = true;
    }

    public function confirmKeyRemoval(AiProviderSettings $settings): void
    {
        if (! $this->showRemoveKeyModal || $this->pendingKeyProvider === null) {
            throw ValidationException::withMessages([
                'provider' => 'Select a provider key before confirming removal.',
            ]);
        }

        $this->removeKey($this->pendingKeyProvider, $settings);
        $this->reset('showRemoveKeyModal', 'pendingKeyProvider');
    }

    public function cancelKeyRemoval(): void
    {
        $this->reset('showRemoveKeyModal', 'pendingKeyProvider');
    }

    public function resetSession(WallpaperService $wallpapers, WorkspaceContext $workspace): void
    {
        $wallpapers->resetSession($workspace->key());

        $this->redirect('/', navigate: false);
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
    <div class="fixed right-5 top-5 z-30 flex items-center gap-2">
        <x-theme-toggle
            class="btn btn-circle btn-soft border border-base-200 bg-base-100/60 backdrop-blur-md"
            aria-label="Toggle color theme" />

        @if($authenticationEnabled)
            <x-button
                wire:click="$toggle('showDrawer')"
                icon="lucide.settings"
                label="{{ auth()->user()->name }}"
                class="btn-soft max-w-[min(16rem,calc(100vw-6rem))] border border-base-200 bg-base-100/60 backdrop-blur-md"
                tooltip-left="Settings"
                aria-label="Settings for {{ auth()->user()->name }}" />
        @else
            <x-button
                wire:click="$toggle('showDrawer')"
                icon="lucide.settings"
                class="btn-circle btn-soft border border-base-200 bg-base-100/60 backdrop-blur-md"
                tooltip-left="Settings"
                aria-label="Settings" />
        @endif
    </div>

    @teleport('body')
        <x-drawer
            wire:model="showDrawer"
            title="Settings"
            subtitle="Manage AI providers{{ $authenticationEnabled ? ' and your account' : '' }}."
            right
            withCloseButton
            closeOnEscape
            class="w-11/12 md:w-[30rem]">
            <x-tabs wire:model="selectedTab" active-class="text-primary" content-class="pt-6">
                <x-tab name="providers-tab" label="Provider Selection" icon="lucide.sparkles">
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

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <div class="fieldset-legend mb-0.5 flex items-center gap-1.5">
                                <x-icon name="lucide.message-square-text" class="size-4 text-base-content/50" />
                                <span>Text provider</span>
                            </div>

                            <x-select
                                aria-label="Text provider"
                                :options="$this->textProviderOptions()"
                                wire:model.live="textProvider" />
                        </div>

                        @if($this->textModelIsCustom())
                            <div class="flex flex-col gap-1.5">
                                <div class="fieldset-legend mb-0.5 flex items-center gap-1.5">
                                    <x-icon name="lucide.cpu" class="size-4 text-base-content/50" />
                                    <span>Text model</span>
                                </div>

                                <x-input
                                    aria-label="Text model"
                                    wire:model="textModel"
                                    placeholder="{{ GenerationProvider::Ollama->defaultModel(GenerationProvider::TEXT) }}"
                                    wire:key="text-model-input-{{ $textProvider }}" />
                            </div>
                        @else
                            <div class="flex flex-col gap-1.5">
                                <div class="fieldset-legend mb-0.5 flex items-center gap-1.5">
                                    <x-icon name="lucide.cpu" class="size-4 text-base-content/50" />
                                    <span>Text model</span>
                                </div>

                                <x-select
                                    aria-label="Text model"
                                    :options="$this->textModelOptions()"
                                    wire:model="textModel"
                                    wire:key="text-model-select-{{ $textProvider }}" />
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <div class="fieldset-legend mb-0.5 flex items-center gap-1.5">
                                <x-icon name="lucide.image" class="size-4 text-base-content/50" />
                                <span>Image provider</span>
                            </div>

                            <x-select
                                aria-label="Image provider"
                                :options="$this->imageProviderOptions()"
                                wire:model.live="imageProvider" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <div class="fieldset-legend mb-0.5 flex items-center gap-1.5">
                                <x-icon name="lucide.cpu" class="size-4 text-base-content/50" />
                                <span>Image model</span>
                            </div>

                            <x-select
                                aria-label="Image model"
                                :options="$this->imageModelOptions()"
                                wire:model="imageModel"
                                wire:key="image-model-{{ $imageProvider }}" />
                        </div>
                    </div>
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
                                    wire:click="requestKeyRemoval('openai')"
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
                                    wire:click="requestKeyRemoval('gemini')"
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

                @if($textProvider === GenerationProvider::Ollama->value)
                    <section class="flex flex-col gap-3" wire:key="ollama-connection">
                        <div>
                            <h3 class="font-semibold">Ollama connection</h3>
                            <p class="text-xs text-base-content/60">The URL must be reachable from the Laravel server. No API key is required for a standard local Ollama installation.</p>
                        </div>

                        <x-input
                            label="Ollama URL"
                            icon="lucide.server"
                            wire:model="ollamaUrl"
                            placeholder="http://localhost:11434" />
                    </section>
                @endif

                        <x-slot:actions>
                            <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                                <x-button
                                    type="button"
                                    wire:click="$set('showResetModal', true)"
                                    icon="lucide.rotate-ccw"
                                    class="btn-ghost text-error"
                                    label="{{ $authenticationEnabled ? 'Reset workspace' : 'Reset session' }}" />
                                <x-button
                                    type="submit"
                                    spinner="save"
                                    icon="lucide.save"
                                    class="btn-primary"
                                    label="Save settings" />
                            </div>
                        </x-slot:actions>
                    </x-form>
                </x-tab>

                @if($authenticationEnabled)
                    <x-tab name="account-tab" label="Account" icon="lucide.user-round-cog">
                        <livewire:account-settings />
                    </x-tab>
                @endif
            </x-tabs>
        </x-drawer>
    @endteleport

    @teleport('body')
        <x-modal
            wire:model="showRemoveKeyModal"
            title="Remove saved API key?"
            subtitle="The provider may stop working immediately."
            separator
            box-class="max-w-lg">
            <x-alert
                icon="lucide.triangle-alert"
                class="alert-warning"
                title="This credential will be permanently removed"
                description="WallAI will fall back to the server environment key when one exists. Otherwise, you must add a new key before using this provider again." />

            <x-slot:actions>
                <x-button
                    type="button"
                    wire:click="cancelKeyRemoval"
                    class="btn-ghost"
                    label="Cancel" />
                <x-button
                    type="button"
                    wire:click="confirmKeyRemoval"
                    spinner="confirmKeyRemoval"
                    icon="lucide.trash-2"
                    class="btn-error"
                    label="Remove API key" />
            </x-slot:actions>
        </x-modal>
    @endteleport

    @teleport('body')
        <x-modal
            wire:model="showResetModal"
            title="{{ $authenticationEnabled ? 'Reset your workspace?' : 'Reset this entire session?' }}"
            subtitle="{{ $authenticationEnabled ? 'Your account stays signed in, but its WallAI data will be cleared.' : 'WallAI will reload with a completely new session.' }}"
            separator
            box-class="max-w-lg">
            <div class="flex flex-col gap-4">
                <x-alert
                    icon="lucide.triangle-alert"
                    class="alert-warning"
                    title="This action cannot be undone"
                    description="All generated images, pending generations, provider choices, models, and saved API keys for {{ $authenticationEnabled ? 'your account' : 'this session' }} will be permanently removed." />

                <p class="text-sm text-base-content/70">
                    Download any images you want to keep before continuing.
                </p>
            </div>

            <x-slot:actions>
                <x-button
                    type="button"
                    wire:click="$set('showResetModal', false)"
                    class="btn-ghost"
                    label="Cancel" />
                <x-button
                    type="button"
                    wire:click="resetSession"
                    spinner="resetSession"
                    icon="lucide.rotate-ccw"
                    class="btn-error"
                    label="Reset and reload" />
            </x-slot:actions>
        </x-modal>
    @endteleport
</div>
