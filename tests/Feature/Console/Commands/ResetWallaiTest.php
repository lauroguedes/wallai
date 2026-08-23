<?php

use App\Enums\ApplicationMode;
use App\Models\AiProviderSetting;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Models\Wallpaper;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Tester\CommandTester;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('cancels a factory reset unless it is explicitly confirmed', function () {
    $settings = ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Authenticated,
    ]);

    $tester = new CommandTester(Artisan::all()['wallai:reset']);
    $tester->setInputs(['no']);

    expect($tester->execute([]))->toBe(1)
        ->and($tester->getDisplay())->toContain('Factory reset cancelled');

    $this->assertModelExists($settings);
});

it('returns WallAI to its uninstalled factory state', function () {
    $settings = ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Authenticated,
    ]);
    $user = User::factory()->admin()->create();
    $providerSettings = AiProviderSetting::factory()->create(['user_id' => $user->id]);
    $wallpaper = Wallpaper::factory()->create([
        'user_id' => $user->id,
        'session_id' => null,
        'path' => "wallpapers/users/{$user->id}/saved.png",
    ]);

    Storage::disk('public')->put($wallpaper->path, 'image');
    Cache::put('wallai-test-state', 'remove-me');
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'invitation-token',
        'created_at' => now(),
    ]);
    DB::table('sessions')->insert([
        'id' => 'factory-reset-session',
        'user_id' => $user->id,
        'payload' => 'session-payload',
        'last_activity' => now()->timestamp,
    ]);
    DB::table('jobs')->insert([
        'queue' => 'notifications',
        'payload' => 'queued-job',
        'attempts' => 0,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    $tester = new CommandTester(Artisan::all()['wallai:reset']);

    expect($tester->execute(['--force' => true]))->toBe(0)
        ->and($tester->getDisplay())->toContain('All users, invitations, sessions, generated images')
        ->toContain('WallAI was reset successfully');

    $this->assertModelMissing($settings);
    $this->assertModelMissing($user);
    $this->assertModelMissing($providerSettings);
    $this->assertModelMissing($wallpaper);

    expect(Cache::get('wallai-test-state'))->toBeNull()
        ->and(DB::table('password_reset_tokens')->exists())->toBeFalse()
        ->and(DB::table('sessions')->exists())->toBeFalse()
        ->and(DB::table('jobs')->exists())->toBeFalse();

    Storage::disk('public')->assertMissing('wallpapers');
});
