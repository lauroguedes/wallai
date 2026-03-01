<?php

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
