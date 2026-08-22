<?php

namespace App\Exceptions;

use App\Enums\GenerationProvider;
use RuntimeException;

class MissingAiCredentialsException extends RuntimeException
{
    public function __construct(public readonly GenerationProvider $provider)
    {
        parent::__construct("No API key is configured for {$provider->label()}.");
    }

    public function getUserMessage(): string
    {
        return "Add your {$this->provider->label()} API key in provider settings before generating.";
    }
}
