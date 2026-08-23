<?php

use App\Enums\GenerationProvider;

it('owns the fixed text and image model catalogs', function () {
    expect(GenerationProvider::OpenAI->defaultModel(GenerationProvider::TEXT))
        ->toBe('gpt-5.6-terra')
        ->and(GenerationProvider::OpenAI->modelOptions(GenerationProvider::TEXT))
        ->toBe([
            ['id' => 'gpt-5.6-sol', 'name' => 'GPT-5.6 Sol (highest capability)'],
            ['id' => 'gpt-5.6-terra', 'name' => 'GPT-5.6 Terra (balanced)'],
            ['id' => 'gpt-5.6-luna', 'name' => 'GPT-5.6 Luna (economy)'],
        ])
        ->and(GenerationProvider::OpenAI->defaultModel(GenerationProvider::IMAGE))
        ->toBe('gpt-image-2')
        ->and(GenerationProvider::Gemini->defaultModel(GenerationProvider::TEXT))
        ->toBe('gemini-3.7-flash')
        ->and(GenerationProvider::Gemini->defaultModel(GenerationProvider::IMAGE))
        ->toBe('gemini-3.1-flash-image-preview');
});

it('defines Ollama as a custom text-only model catalog', function () {
    expect(GenerationProvider::Ollama->supports(GenerationProvider::TEXT))->toBeTrue()
        ->and(GenerationProvider::Ollama->allowsCustomModel(GenerationProvider::TEXT))->toBeTrue()
        ->and(GenerationProvider::Ollama->defaultModel(GenerationProvider::TEXT))->toBe('llama3.1:8b')
        ->and(GenerationProvider::Ollama->supports(GenerationProvider::IMAGE))->toBeFalse()
        ->and(GenerationProvider::Ollama->modelOptions(GenerationProvider::IMAGE))->toBe([])
        ->and(GenerationProvider::Ollama->defaultModel(GenerationProvider::IMAGE))->toBeNull();
});
