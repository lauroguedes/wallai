<?php

use App\Exceptions\ServiceGeneratorException;

it('creates image generation exception with correct properties', function () {
    $original = new \RuntimeException('API rate limit exceeded');

    $exception = ServiceGeneratorException::imageGeneration($original, ['prompt' => 'sunset']);

    expect($exception)
        ->getMessage()->toBe('API rate limit exceeded')
        ->getUserMessage()->toBe('We could not generate your wallpaper. Please try again.')
        ->getOperation()->toBe('image_generation')
        ->getPrevious()->toBe($original);
});

it('creates prompt generation exception with correct properties', function () {
    $original = new \RuntimeException('Connection timeout');

    $exception = ServiceGeneratorException::promptGeneration($original, ['style' => 'abstract']);

    expect($exception)
        ->getMessage()->toBe('Connection timeout')
        ->getUserMessage()->toBe('We could not generate a prompt. Please try again.')
        ->getOperation()->toBe('prompt_generation')
        ->getPrevious()->toBe($original);
});

it('creates download failed exception with correct properties', function () {
    $original = new \RuntimeException('File not found');

    $exception = ServiceGeneratorException::downloadFailed($original);

    expect($exception)
        ->getMessage()->toBe('File not found')
        ->getUserMessage()->toBe('The wallpaper could not be downloaded. Please try again.')
        ->getOperation()->toBe('download')
        ->getPrevious()->toBe($original);
});

it('allows custom user message via constructor', function () {
    $exception = new ServiceGeneratorException(
        message: 'Technical failure',
        userMessage: 'Custom friendly message',
        operation: 'custom_op',
    );

    expect($exception)
        ->getMessage()->toBe('Technical failure')
        ->getUserMessage()->toBe('Custom friendly message')
        ->getOperation()->toBe('custom_op');
});

it('defaults to generic user message', function () {
    $exception = new ServiceGeneratorException('Some error');

    expect($exception)
        ->getUserMessage()->toBe('Something went wrong. Please try again.')
        ->getOperation()->toBe('unknown');
});
