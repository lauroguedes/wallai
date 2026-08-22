<?php

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Enums\GenerationProvider;
use App\Jobs\GenerateWallpaper;
use App\Models\AiProviderSetting;
use App\Services\WallpaperService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'ai.providers.openai.key' => null,
        'ai.providers.gemini.key' => null,
    ]);
});

it('renders the provider settings drawer with independent capability selectors', function () {
    Livewire::test('provider-settings')
        ->assertSet('showDrawer', false)
        ->assertSet('textProvider', GenerationProvider::Gemini->value)
        ->assertSet('imageProvider', GenerationProvider::Gemini->value)
        ->assertSee('Text provider')
        ->assertSee('Image provider')
        ->assertSee('Google Gemini');
});

it('opens the drawer when another component requests provider settings', function () {
    Livewire::test('provider-settings')
        ->dispatch('open-provider-settings')
        ->assertSet('showDrawer', true);
});

it('requires a key for every selected provider', function () {
    Livewire::test('provider-settings')
        ->set('textProvider', GenerationProvider::OpenAI->value)
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
        ->and($settings->image_provider)->toBe(GenerationProvider::Gemini)
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

it('uses environment keys as a backwards compatible fallback', function () {
    config(['ai.providers.gemini.key' => 'server-gemini-key']);

    Livewire::test('provider-settings')
        ->call('save')
        ->assertHasNoErrors();

    expect(AiProviderSetting::query()->sole()->gemini_api_key)->toBeNull();
});

it('forgets the session settings and encrypted keys', function () {
    Livewire::test('provider-settings')
        ->set('geminiApiKey', 'gemini-secret-key')
        ->call('save')
        ->call('forget')
        ->assertSet('hasStoredGeminiKey', false);

    expect(AiProviderSetting::query()->exists())->toBeFalse()
        ->and(session('ai_provider_settings_id'))->toBeNull();
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
            && $job->imageProvider === GenerationProvider::Gemini
            && $job->providerSettingsId === AiProviderSetting::query()->sole()->getKey()
            && ! str_contains(serialize($job), 'queue-openai-secret')
            && ! str_contains(serialize($job), 'queue-gemini-secret');
    });
});

it('renders the settings button on the home page', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('AI provider settings');
});
