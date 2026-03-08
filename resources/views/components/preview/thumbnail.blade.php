@props(['wallpaper', 'index', 'isActive', 'size' => 'w-16 h-16', 'keyPrefix' => 'thumb'])

<div class="relative shrink-0 group" wire:key="{{ $keyPrefix }}-{{ $wallpaper['id'] }}">
    <button wire:click="selectWallpaper({{ $index }})"
            class="{{ $size }} rounded-lg overflow-hidden border-2 transition-all hover:scale-105
                   {{ $isActive ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }}">
        <img src="{{ $wallpaper['url'] }}" alt="Thumbnail" class="object-cover w-full h-full" loading="lazy" />
    </button>
    <button wire:click="deleteWallpaper('{{ $wallpaper['id'] }}')"
            class="absolute -top-1.5 -right-1.5 btn btn-error btn-circle btn-xs opacity-0 group-hover:opacity-100 transition-opacity z-10">
        <x-icon name="lucide.trash" class="w-3 h-3" />
    </button>
</div>
