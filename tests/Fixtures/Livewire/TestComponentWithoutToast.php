<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Component;

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
