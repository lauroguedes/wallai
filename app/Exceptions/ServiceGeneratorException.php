<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceGeneratorException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        protected string $userMessage = 'Something went wrong. Please try again.',
        protected string $operation = 'unknown',
        protected array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create an exception for image generation failures.
     *
     * @param  array<string, mixed>  $context
     */
    public static function imageGeneration(Throwable $previous, array $context = []): self
    {
        return new self(
            message: $previous->getMessage(),
            userMessage: 'We could not generate your wallpaper. Please try again.',
            operation: 'image_generation',
            context: $context,
            previous: $previous,
        );
    }

    /**
     * Create an exception for prompt generation failures.
     *
     * @param  array<string, mixed>  $context
     */
    public static function promptGeneration(Throwable $previous, array $context = []): self
    {
        return new self(
            message: $previous->getMessage(),
            userMessage: 'We could not generate a prompt. Please try again.',
            operation: 'prompt_generation',
            context: $context,
            previous: $previous,
        );
    }

    /**
     * Create an exception for download failures.
     *
     * @param  array<string, mixed>  $context
     */
    public static function downloadFailed(Throwable $previous, array $context = []): self
    {
        return new self(
            message: $previous->getMessage(),
            userMessage: 'The wallpaper could not be downloaded. Please try again.',
            operation: 'download',
            context: $context,
            previous: $previous,
        );
    }

    /**
     * Get the user-friendly message for display in the UI.
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get the operation that failed.
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Report the exception to the application log.
     */
    public function report(): void
    {
        Log::error("WallAI service error [{$this->operation}]: {$this->getMessage()}", [
            'operation' => $this->operation,
            'user_message' => $this->userMessage,
            'context' => $this->context,
            'exception' => $this->getPrevious(),
        ]);
    }
}
