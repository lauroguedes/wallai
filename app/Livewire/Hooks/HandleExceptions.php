<?php

namespace App\Livewire\Hooks;

use App\Exceptions\ServiceGeneratorException;
use Livewire\ComponentHook;
use Livewire\ComponentHookRegistry;
use Mary\Traits\Toast;

use function Livewire\on;

class HandleExceptions extends ComponentHook
{
    /**
     * Self-register mount/hydrate listeners so the hook works
     * even when registered after ComponentHookRegistry::boot().
     */
    public static function provide(): void
    {
        on('mount', function ($component) {
            ComponentHookRegistry::initializeHook(static::class, $component);
        });

        on('hydrate', function ($component) {
            ComponentHookRegistry::initializeHook(static::class, $component);
        });
    }

    /**
     * Intercept exceptions thrown in Livewire components.
     *
     * If the component uses the Toast trait, display a friendly
     * error message and suppress Livewire's default error modal.
     */
    public function exception($e, $stopPropagation): void
    {
        if (! in_array(Toast::class, class_uses_recursive($this->component))) {
            return;
        }

        report($e);

        $message = $e instanceof ServiceGeneratorException
            ? $e->getUserMessage()
            : 'Something went wrong. Please try again.';

        $this->component->error($message);

        $stopPropagation();
    }
}
