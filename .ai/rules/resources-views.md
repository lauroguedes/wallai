---
paths:
  - 'resources/views/**'
---

# Resources Views

## Ollama is a text-only provider
Laravel AI 0.11 supports Ollama for text generation and embeddings, but not image generation. Show Ollama only in text provider choices; use a server-reachable base URL and allow arbitrary installed local model names.

## Use Mary UI modals for confirmations
All destructive or confirmation flows must use a Mary UI <x-modal> controlled by Livewire state. Never use wire:confirm, window.confirm, or browser-native dialogs. Re-load and re-authorize the target inside the confirmed action before mutating data.

## Use server actions for conditional Livewire markup
Client-side `$wire` assignments are appropriate for opening Mary UI modals that are already rendered. When a Livewire property controls server-rendered Blade conditionals such as `@if`, change it through `wire:click` or another Livewire request so the component rerenders; deferred Alpine assignment alone will not replace the markup.
