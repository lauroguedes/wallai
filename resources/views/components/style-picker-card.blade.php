@props(['style', 'selected' => false])

<div {{ $attributes->class([
    'relative rounded-xl overflow-hidden cursor-pointer h-32 group transition-all',
    'ring-2 ring-primary ring-offset-2 ring-offset-base-100' => $selected,
]) }}>
    <img src="{{ $style->image() }}" alt="{{ $style->title() }}"
         class="absolute inset-0 w-full h-full object-cover transition-transform group-hover:scale-105" />
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-black/10"></div>
    <div class="relative z-10 flex flex-col justify-end h-full p-3">
        <h4 class="text-white font-semibold text-sm leading-tight">{{ $style->title() }}</h4>
        <p class="text-white/70 text-xs mt-0.5 line-clamp-2">{{ $style->description() }}</p>
    </div>
</div>
