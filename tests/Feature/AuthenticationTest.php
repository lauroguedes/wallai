<?php

use App\Enums\ApplicationMode;
use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Authenticated,
    ]);
});

it('requires login when authentication is enabled', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get('/login')->assertOk()->assertSee('Welcome back');
});

it('renders account settings for an authenticated user', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSeeLivewire('account-settings')
        ->assertSee('Your profile')
        ->assertSee('Users');
});

it('logs in with valid credentials and rotates the session', function () {
    $user = User::factory()->create(['password' => 'password-for-wallai']);

    Livewire::test('pages::login')
        ->set('email', $user->email)
        ->set('password', 'password-for-wallai')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    Livewire::test('pages::login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

it('rejects credentials for an inactive user', function () {
    $user = User::factory()->inactive()->create(['password' => 'password-for-wallai']);

    Livewire::test('pages::login')
        ->set('email', $user->email)
        ->set('password', 'password-for-wallai')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

it('logs out a user whose account was deactivated', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('activates an invited account with its token', function () {
    $user = User::factory()->inactive()->unverified()->create([
        'password' => str()->random(64),
    ]);
    $token = Password::broker()->createToken($user);

    Livewire::withQueryParams(['email' => $user->email])
        ->test('pages::accept-invitation', ['token' => $token])
        ->set('password', 'a strong invited password')
        ->set('passwordConfirmation', 'a strong invited password')
        ->call('acceptInvitation')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('a strong invited password', $user->password))->toBeTrue()
        ->and(Auth::id())->toBe($user->id);
});
