<?php

use App\Enums\ApplicationMode;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Enums\GenerationProvider;
use App\Jobs\GenerateWallpaper;
use App\Models\AiProviderSetting;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\WallpaperService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Session,
    ]);

    config([
        'ai.providers.openai.key' => null,
        'ai.providers.gemini.key' => null,
    ]);
});

it('renders the provider settings drawer with independent capability selectors', function () {
    Livewire::test('provider-settings')
        ->assertSet('showDrawer', false)
        ->assertSet('textProvider', GenerationProvider::Gemini->value)
        ->assertSet('textModel', 'gemini-3.7-flash')
        ->assertSet('imageProvider', GenerationProvider::Gemini->value)
        ->assertSet('imageModel', 'gemini-3.1-flash-image-preview')
        ->assertSee('Text provider')
        ->assertSee('Text model')
        ->assertSee('Image provider')
        ->assertSee('Image model')
        ->assertSee('Ollama')
        ->assertSeeHtml('grid grid-cols-1 gap-4 sm:grid-cols-2')
        ->assertSee('Google Gemini');
});

it('separates provider and account settings with Mary UI tabs', function () {
    ApplicationSetting::query()->update(['mode' => ApplicationMode::Authenticated]);
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('provider-settings')
        ->assertSet('selectedTab', 'providers-tab')
        ->assertSee($admin->name)
        ->assertSee('data-tip="Settings"', escape: false)
        ->assertSee("aria-label=\"Settings for {$admin->name}\"", escape: false)
        ->assertSee('aria-label="Toggle color theme"', escape: false)
        ->assertSee('Provider Selection')
        ->assertSee('Account')
        ->assertSeeLivewire('account-settings');
});

it('only offers providers that support each generation capability', function () {
    $component = Livewire::test('provider-settings');

    expect(collect($component->instance()->textProviderOptions())->pluck('id')->all())
        ->toContain(GenerationProvider::OpenAI->value, GenerationProvider::Gemini->value, GenerationProvider::Ollama->value)
        ->and(collect($component->instance()->imageProviderOptions())->pluck('id')->all())
        ->toContain(GenerationProvider::OpenAI->value, GenerationProvider::Gemini->value)
        ->not->toContain(GenerationProvider::Ollama->value);
});

it('loads compatible models when a provider changes', function () {
    $component = Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::OpenAI->value)
        ->assertSet('textModel', GenerationProvider::OpenAI->defaultModel(GenerationProvider::TEXT))
        ->assertSee('GPT-5.6 Sol')
        ->assertSee('GPT-5.6 Terra')
        ->assertSee('GPT-5.6 Luna')
        ->assertDontSee('GPT-5.4 Pro')
        ->assertDontSee('Gemini 3.5 Flash-Lite')
        ->set('imageProvider', GenerationProvider::OpenAI->value)
        ->assertSet('imageModel', 'gpt-image-2')
        ->assertSee('GPT Image 2');

    expect(collect($component->instance()->textModelOptions())->pluck('id')->all())
        ->toBe(array_column(GenerationProvider::OpenAI->modelOptions(GenerationProvider::TEXT), 'id'));
});

it('rejects a model that is incompatible with its provider', function () {
    Livewire::test('provider-settings')
        ->set('textModel', 'gpt-5.4')
        ->call('save')
        ->assertHasErrors(['textModel']);

    expect(AiProviderSetting::query()->exists())->toBeFalse();
});

it('saves an Ollama URL and custom local text model without an API key', function () {
    config(['ai.providers.gemini.key' => 'server-gemini-key']);

    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::Ollama->value)
        ->assertSet('textModel', 'llama3.1:8b')
        ->assertSee('Ollama connection')
        ->set('textModel', 'qwen3:14b')
        ->set('ollamaUrl', 'http://host.docker.internal:11434/')
        ->call('save')
        ->assertHasNoErrors();

    $settings = AiProviderSetting::query()->sole();

    expect($settings->text_provider)->toBe(GenerationProvider::Ollama)
        ->and($settings->text_model)->toBe('qwen3:14b')
        ->and($settings->ollama_url)->toBe('http://host.docker.internal:11434')
        ->and($settings->openai_api_key)->toBeNull()
        ->and($settings->gemini_api_key)->toBeNull();
});

it('validates the Ollama server URL', function () {
    config(['ai.providers.gemini.key' => 'server-gemini-key']);

    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::Ollama->value)
        ->set('ollamaUrl', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['ollamaUrl']);

    expect(AiProviderSetting::query()->exists())->toBeFalse();
});

it('rejects an Ollama host outside the installation allowlist', function () {
    config(['ai.providers.gemini.key' => 'server-gemini-key']);

    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::Ollama->value)
        ->set('ollamaUrl', 'http://metadata.internal:11434')
        ->call('save')
        ->assertHasErrors(['ollamaUrl']);

    expect(AiProviderSetting::query()->exists())->toBeFalse();
});

it('rejects Ollama as an image provider', function () {
    Livewire::test('provider-settings')
        ->set('imageProvider', GenerationProvider::Ollama->value)
        ->call('save')
        ->assertHasErrors(['imageProvider', 'imageModel']);

    expect(AiProviderSetting::query()->exists())->toBeFalse();
});

it('opens the drawer when another component requests provider settings', function () {
    Livewire::test('provider-settings')
        ->dispatch('open-provider-settings')
        ->assertSet('showDrawer', true);
});

it('requires a key for every selected provider', function () {
    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::OpenAI->value)
        ->set('textModel', 'gpt-5.6-sol')
        ->set('imageProvider', GenerationProvider::Gemini->value)
        ->call('save')
        ->assertHasErrors(['openAiApiKey', 'geminiApiKey']);

    expect(AiProviderSetting::query()->exists())->toBeFalse();
});

it('encrypts keys and never renders them after saving', function () {
    $openAiKey = 'openai-secret-key';
    $geminiKey = 'gemini-secret-key';

    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::OpenAI->value)
        ->set('textModel', 'gpt-5.6-sol')
        ->set('imageProvider', GenerationProvider::Gemini->value)
        ->set('openAiApiKey', $openAiKey)
        ->set('geminiApiKey', $geminiKey)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('openAiApiKey', '')
        ->assertSet('geminiApiKey', '')
        ->assertDontSee($openAiKey)
        ->assertDontSee($geminiKey);

    $settings = AiProviderSetting::query()->sole();
    $raw = DB::table('ai_provider_settings')->where('id', $settings->getKey())->first();

    expect($settings->text_provider)->toBe(GenerationProvider::OpenAI)
        ->and($settings->text_model)->toBe('gpt-5.6-sol')
        ->and($settings->image_provider)->toBe(GenerationProvider::Gemini)
        ->and($settings->image_model)->toBe('gemini-3.1-flash-image-preview')
        ->and($settings->openai_api_key)->toBe($openAiKey)
        ->and($settings->gemini_api_key)->toBe($geminiKey)
        ->and($raw->openai_api_key)->not->toBe($openAiKey)
        ->and($raw->gemini_api_key)->not->toBe($geminiKey)
        ->and(session('ai_provider_settings_id'))->toBe($settings->getKey());
});

it('keeps an existing key when its input is left blank', function () {
    Livewire::test('provider-settings')
        ->set('openAiApiKey', 'first-openai-key')
        ->set('geminiApiKey', 'first-gemini-key')
        ->call('save')
        ->set('openAiApiKey', '')
        ->set('geminiApiKey', '')
        ->call('save')
        ->assertHasNoErrors();

    $settings = AiProviderSetting::query()->sole();

    expect($settings->openai_api_key)->toBe('first-openai-key')
        ->and($settings->gemini_api_key)->toBe('first-gemini-key');
});

it('uses a Mary UI modal before removing a saved provider key', function () {
    Livewire::test('provider-settings')
        ->set('openAiApiKey', 'first-openai-key')
        ->set('geminiApiKey', 'first-gemini-key')
        ->call('save')
        ->call('requestKeyRemoval', GenerationProvider::OpenAI->value)
        ->assertSet('showRemoveKeyModal', true)
        ->assertSet('pendingKeyProvider', GenerationProvider::OpenAI->value)
        ->assertSee('Remove saved API key?')
        ->assertDontSeeHtml('wire:confirm')
        ->call('confirmKeyRemoval')
        ->assertHasNoErrors()
        ->assertSet('showRemoveKeyModal', false);

    expect(AiProviderSetting::query()->sole()->openai_api_key)->toBeNull();
});

it('uses environment keys as a backwards compatible fallback', function () {
    config(['ai.providers.gemini.key' => 'server-gemini-key']);

    Livewire::test('provider-settings')
        ->call('save')
        ->assertHasNoErrors();

    expect(AiProviderSetting::query()->sole()->gemini_api_key)->toBeNull();
});

it('shows a Mary UI warning before resetting the session', function () {
    Livewire::test('provider-settings')
        ->assertSet('showResetModal', false)
        ->set('showResetModal', true)
        ->assertSee('Reset this entire session?')
        ->assertSee('This action cannot be undone')
        ->assertSee('Reset and reload');
});

it('resets all session data, generated images, caches, and provider settings', function () {
    Storage::fake('public');

    $component = Livewire::test('provider-settings')
        ->set('geminiApiKey', 'gemini-secret-key')
        ->call('save');

    $settings = AiProviderSetting::query()->sole();
    $oldSessionId = session()->getId();
    $jobId = 'reset-job-id';

    session()->put('temporary-preference', 'remove-me');
    Storage::disk('public')->put("wallpapers/{$oldSessionId}/mobile.png", 'mobile-image');
    Storage::disk('public')->put("wallpapers/{$oldSessionId}/desktop.png", 'desktop-image');
    Cache::put("wallpapers:{$oldSessionId}:mobile", [['id' => 'mobile.png']]);
    Cache::put("wallpapers:{$oldSessionId}:desktop", [['id' => 'desktop.png']]);
    Cache::put("pending_jobs:{$oldSessionId}", 1);
    Cache::put("wallpaper_jobs:{$oldSessionId}", [$jobId]);
    Cache::put("wallpaper_job:{$jobId}", ['status' => 'pending']);

    $component
        ->set('showResetModal', true)
        ->call('resetSession')
        ->assertRedirect('/');

    expect(AiProviderSetting::query()->find($settings->getKey()))->toBeNull()
        ->and(session()->getId())->not->toBe($oldSessionId)
        ->and(session('temporary-preference'))->toBeNull()
        ->and(Cache::get("wallpapers:{$oldSessionId}:mobile"))->toBeNull()
        ->and(Cache::get("wallpapers:{$oldSessionId}:desktop"))->toBeNull()
        ->and(Cache::get("pending_jobs:{$oldSessionId}"))->toBeNull()
        ->and(Cache::get("wallpaper_jobs:{$oldSessionId}"))->toBeNull()
        ->and(Cache::get("wallpaper_job:{$jobId}"))->toBeNull()
        ->and(Cache::has("wallpaper_session:{$oldSessionId}:reset"))->toBeTrue()
        ->and(Storage::disk('public')->exists("wallpapers/{$oldSessionId}"))->toBeFalse();
});

it('rejects unsupported providers in component actions', function () {
    Livewire::test('provider-settings')
        ->call('removeKey', 'unsupported')
        ->assertHasErrors(['provider']);
});

it('snapshots provider choices in queued jobs without serializing plaintext keys', function () {
    Queue::fake();

    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::OpenAI->value)
        ->set('textModel', 'gpt-5.6-luna')
        ->set('imageProvider', GenerationProvider::Gemini->value)
        ->set('openAiApiKey', 'queue-openai-secret')
        ->set('geminiApiKey', 'queue-gemini-secret')
        ->call('save');

    app(WallpaperService::class)->dispatchGeneration(
        session()->getId(),
        'a quiet mountain lake',
        BackgroundStyle::NaturalLandscape,
        DeviceType::Mobile,
    );

    Queue::assertPushed(GenerateWallpaper::class, function (GenerateWallpaper $job): bool {
        return $job->textProvider === GenerationProvider::OpenAI
            && $job->textModel === 'gpt-5.6-luna'
            && $job->imageProvider === GenerationProvider::Gemini
            && $job->imageModel === 'gemini-3.1-flash-image-preview'
            && $job->providerSettingsId === AiProviderSetting::query()->sole()->getKey()
            && ! str_contains(serialize($job), 'queue-openai-secret')
            && ! str_contains(serialize($job), 'queue-gemini-secret');
    });
});

it('renders the settings button on the home page', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('data-tip="Settings"', escape: false)
        ->assertSee('aria-label="Toggle color theme"', escape: false)
        ->assertSee('aria-label="Settings"', escape: false)
        ->assertDontSee('AI provider settings');
});
