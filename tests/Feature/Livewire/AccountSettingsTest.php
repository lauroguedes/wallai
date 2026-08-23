<?php

use App\Enums\ApplicationMode;
use App\Models\AiProviderSetting;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Models\Wallpaper;
use App\Notifications\UserInvitation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Authenticated,
    ]);
});

it('updates the signed-in user profile and password', function () {
    $user = User::factory()->create(['password' => 'old-password-value']);

    Livewire::actingAs($user)
        ->test('account-settings')
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('saveProfile')
        ->assertHasNoErrors()
        ->set('currentPassword', 'old-password-value')
        ->set('password', 'new-password-value')
        ->set('passwordConfirmation', 'new-password-value')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.com')
        ->and(Hash::check('new-password-value', $user->password))->toBeTrue();
});

it('allows an administrator to invite another user', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('account-settings')
        ->set('inviteName', 'Invited User')
        ->set('inviteEmail', 'invited@example.com')
        ->call('inviteUser')
        ->assertHasNoErrors();

    $invited = User::query()->where('email', 'invited@example.com')->sole();

    expect($invited->name)->toBe('Invited User')
        ->and($invited->is_admin)->toBeFalse()
        ->and($invited->is_active)->toBeFalse()
        ->and($invited->email_verified_at)->toBeNull();

    Notification::assertSentTo(
        $invited,
        UserInvitation::class,
        fn (UserInvitation $notification): bool => $notification->viaQueues()['mail'] === 'notifications',
    );
});

it('allows an administrator to resend a pending invitation', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $invited = User::factory()->inactive()->unverified()->create();

    Livewire::actingAs($admin)
        ->test('account-settings')
        ->call('resendInvitation', $invited->id)
        ->assertHasNoErrors();

    Notification::assertSentTo($invited, UserInvitation::class);
});

it('allows an administrator to deactivate and reactivate a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $component = Livewire::actingAs($admin)->test('account-settings');

    $component
        ->call('requestUserAction', $user->id, 'deactivate')
        ->assertSet('showUserConfirmationModal', true)
        ->assertSet('pendingUserName', $user->name)
        ->assertSee('Deactivate user?')
        ->assertDontSeeHtml('wire:confirm')
        ->call('confirmUserAction')
        ->assertHasNoErrors()
        ->assertSet('showUserConfirmationModal', false);

    expect($user->refresh()->is_active)->toBeFalse();

    $component->call('activateUser', $user->id)->assertHasNoErrors();
    expect($user->refresh()->is_active)->toBeTrue();
});

it('deletes a user and all workspace resources', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $settings = AiProviderSetting::factory()->create(['user_id' => $user->id]);
    $wallpaper = Wallpaper::factory()->create([
        'user_id' => $user->id,
        'session_id' => null,
        'path' => "wallpapers/users/{$user->id}/saved.png",
    ]);
    Storage::disk('public')->put($wallpaper->path, 'image');

    Livewire::actingAs($admin)
        ->test('account-settings')
        ->call('requestUserAction', $user->id, 'delete')
        ->assertSet('showUserConfirmationModal', true)
        ->assertSee('Their account, provider settings, and generated images will be permanently deleted.')
        ->call('confirmUserAction')
        ->assertHasNoErrors()
        ->assertSet('showUserConfirmationModal', false);

    $this->assertModelMissing($user);
    $this->assertModelMissing($settings);
    $this->assertModelMissing($wallpaper);
    Storage::disk('public')->assertMissing($wallpaper->path);
});

it('prevents an administrator from managing their own access', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('account-settings')
        ->call('deactivateUser', $admin->id)
        ->assertForbidden();

    expect($admin->refresh()->is_active)->toBeTrue();
});

it('forbids regular users from inviting accounts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('account-settings')
        ->set('inviteName', 'Blocked User')
        ->set('inviteEmail', 'blocked@example.com')
        ->call('inviteUser')
        ->assertForbidden();

    expect(User::query()->where('email', 'blocked@example.com')->exists())->toBeFalse();
});
