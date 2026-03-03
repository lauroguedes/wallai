<?php

use App\Exceptions\ServiceGeneratorException;
use Livewire\Component;
use Livewire\Livewire;
use Mary\Traits\Toast;

/**
 * Test component that uses Toast and throws exceptions.
 */
class TestComponentWithToast extends Component
{
    use Toast;

    public function throwGenericException(): void
    {
        throw new \RuntimeException('Unexpected failure');
    }

    public function throwServiceException(): void
    {
        throw ServiceGeneratorException::imageGeneration(
            new \RuntimeException('API rate limit'),
        );
    }

    public function render(): string
    {
        return '<div>test</div>';
    }
}

/**
 * Test component WITHOUT Toast trait.
 */
class TestComponentWithoutToast extends Component
{
    public function throwException(): void
    {
        throw new \RuntimeException('Unhandled error');
    }

    public function render(): string
    {
        return '<div>test</div>';
    }
}

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
})->throws(\RuntimeException::class, 'Unhandled error');
