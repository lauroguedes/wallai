@props(['style'])

<div {{ $attributes->class(['relative rounded-xl overflow-hidden cursor-pointer h-25 w-full group']) }}>
    <img src="{{ $style->image() }}" alt="{{ $style->title() }}"
         class="absolute inset-0 w-full h-full object-cover transition-transform group-hover:scale-105" />
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-black/20"></div>
    <div class="relative z-10 flex items-end h-full p-3">
        <div>
            <h4 class="text-white font-semibold text-sm leading-tight">{{ $style->title() }}</h4>
            <p class="text-white/70 text-xs mt-0.5 line-clamp-1">{{ $style->description() }}</p>
        </div>
    </div>
</div>
