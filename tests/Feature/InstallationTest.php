<?php

use App\Enums\ApplicationMode;
use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('redirects the first request to setup', function () {
    $this->get('/')->assertRedirect(route('setup'));

    $this->get('/setup')
        ->assertOk()
        ->assertSee('Use authentication')
        ->assertSee('Use browser sessions');
});

it('installs in session mode without creating a user', function () {
    Livewire::test('pages::setup')
        ->call('installWithoutAuthentication')
        ->assertRedirect(route('home'));

    expect(ApplicationSetting::query()->sole()->mode)->toBe(ApplicationMode::Session)
        ->and(User::query()->exists())->toBeFalse();

    $this->get('/')->assertOk();
    $this->get('/login')->assertRedirect(route('home'));
});

it('creates the first user as the administrator', function () {
    Livewire::test('pages::setup')
        ->call('chooseAuthentication')
        ->assertSet('showAdminForm', true)
        ->set('name', 'Admin User')
        ->set('email', 'ADMIN@example.com')
        ->set('password', 'correct horse battery staple')
        ->set('passwordConfirmation', 'correct horse battery staple')
        ->call('createAdmin')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $user = User::query()->sole();

    expect(ApplicationSetting::query()->sole()->mode)->toBe(ApplicationMode::Authenticated)
        ->and($user->name)->toBe('Admin User')
        ->and($user->email)->toBe('admin@example.com')
        ->and($user->is_admin)->toBeTrue()
        ->and(Hash::check('correct horse battery staple', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('prevents setup from running again', function () {
    ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Session,
    ]);

    $this->get('/setup')->assertRedirect(route('home'));
});
