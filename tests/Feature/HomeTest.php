<?php

use App\Enums\ApplicationMode;
use App\Models\ApplicationSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    ApplicationSetting::factory()->create([
        'id' => 1,
        'mode' => ApplicationMode::Session,
    ]);
});

it('returns a successful response', function () {
    $this->get('/')->assertStatus(200);
});

it('renders the prompt-form component', function () {
    $this->get('/')
        ->assertSeeLivewire('prompt-form');
});

it('renders the preview component', function () {
    $this->get('/')
        ->assertSeeLivewire('preview');
});

it('renders the adaptive WallAI logo', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-wallai-logo', escape: false)
        ->assertSee('fill-primary', escape: false)
        ->assertSee('fill-secondary', escape: false)
        ->assertSee('fill-base-content', escape: false);
});

it('renders accessible device preview controls', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('device-toggle-button', escape: false)
        ->assertSee('aria-label="Mobile preview"', escape: false)
        ->assertSee('aria-label="Desktop preview"', escape: false)
        ->assertSee(':aria-pressed="deviceType === \'mobile\'"', escape: false)
        ->assertSee(':aria-pressed="deviceType === \'desktop\'"', escape: false);
});

it('does not render account controls in session mode', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSeeLivewire('account-settings');
});
