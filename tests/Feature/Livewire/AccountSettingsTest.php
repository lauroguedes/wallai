<?php

use App\Enums\ApplicationMode;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
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
        ->and($invited->email_verified_at)->toBeNull();

    Notification::assertSentTo($invited, UserInvitation::class);
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
