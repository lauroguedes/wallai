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

it('does not render account controls in session mode', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSeeLivewire('account-settings');
});
