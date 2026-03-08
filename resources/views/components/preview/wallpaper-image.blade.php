@props(['url'])

<img wire:replace loading="lazy" class="object-cover w-full h-full" alt="Wallpaper" src="{{ $url }}" />
