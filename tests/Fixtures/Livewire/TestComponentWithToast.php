<?php

namespace Tests\Fixtures\Livewire;

use App\Exceptions\ServiceGeneratorException;
use Livewire\Component;
use Mary\Traits\Toast;

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
