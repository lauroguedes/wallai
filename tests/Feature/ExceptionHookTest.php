<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\TestComponentWithoutToast;
use Tests\Fixtures\Livewire\TestComponentWithToast;

it('catches generic exceptions and shows friendly toast on components with Toast trait', function () {
    Livewire::test(TestComponentWithToast::class)
        ->call('throwGenericException')
        ->assertStatus(200);
});

it('catches ServiceGeneratorException and shows specific friendly message', function () {
    Livewire::test(TestComponentWithToast::class)
        ->call('throwServiceException')
        ->assertStatus(200);
});

it('does not intercept exceptions on components without Toast trait', function () {
    Livewire::test(TestComponentWithoutToast::class)
        ->call('throwException');
})->throws(RuntimeException::class, 'Unhandled error');
