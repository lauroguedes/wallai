<?php

use App\Enums\ApplicationMode;
use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use App\Enums\GenerationProvider;
use App\Models\AiProviderSetting;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Models\Wallpaper;
use App\Services\AiProviderSettings;
use App\Services\WallpaperService;
use App\Services\WorkspaceContext;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Authenticated,
    ]);
});

it('stores independent provider settings for each user', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $settings = app(AiProviderSettings::class);

    $this->actingAs($firstUser);
    $firstSettings = $settings->save(
        GenerationProvider::OpenAI,
        GenerationProvider::OpenAI->defaultModel(GenerationProvider::TEXT),
        GenerationProvider::Gemini,
        GenerationProvider::Gemini->defaultModel(GenerationProvider::IMAGE),
        'first-openai-key',
        'first-gemini-key',
    );

    $this->actingAs($secondUser);
    expect($settings->current())->toBeNull();

    $secondSettings = $settings->save(
        GenerationProvider::Gemini,
        GenerationProvider::Gemini->defaultModel(GenerationProvider::TEXT),
        GenerationProvider::OpenAI,
        GenerationProvider::OpenAI->defaultModel(GenerationProvider::IMAGE),
        'second-openai-key',
        'second-gemini-key',
    );

    expect($firstSettings->user_id)->toBe($firstUser->id)
        ->and($secondSettings->user_id)->toBe($secondUser->id)
        ->and(AiProviderSetting::query()->count())->toBe(2);
});

it('persists completed wallpapers under their authenticated owner', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $workspaceKey = app(WorkspaceContext::class)->key();
    $path = "wallpapers/users/{$user->id}/generated.png";
    Storage::disk('public')->put($path, 'image');

    app(WallpaperService::class)->completeGeneration($workspaceKey, 'job-1', DeviceType::Mobile, [
        'id' => 'generated.png',
        'url' => "/storage/{$path}",
        'path' => $path,
        'extension' => 'png',
        'style' => BackgroundStyle::PhotoRealist->value,
    ]);

    $wallpaper = Wallpaper::query()->sole();

    expect($workspaceKey)->toBe("user:{$user->id}")
        ->and($wallpaper->user_id)->toBe($user->id)
        ->and($wallpaper->session_id)->toBeNull()
        ->and(app(WallpaperService::class)->getSessionWallpapers($workspaceKey, DeviceType::Mobile))->toHaveCount(1);
});

it('resets user data without logging the user out', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $settings = AiProviderSetting::factory()->create(['user_id' => $user->id]);
    $wallpaper = Wallpaper::factory()->create([
        'user_id' => $user->id,
        'session_id' => null,
        'path' => "wallpapers/users/{$user->id}/saved.png",
    ]);
    Storage::disk('public')->put($wallpaper->path, 'image');

    app(WallpaperService::class)->resetSession("user:{$user->id}");

    $this->assertAuthenticatedAs($user);
    expect(AiProviderSetting::query()->find($settings->id))->toBeNull()
        ->and(Wallpaper::query()->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($wallpaper->path);
});
