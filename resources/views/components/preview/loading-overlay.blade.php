@props(['loadingPhrase'])

<div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-linear-to-tr from-slate-700 to-gray-900">
    <span class="loading loading-spinner loading-lg text-primary"></span>
    <span class="text-sm text-base-content/70 animate-pulse">{{ $loadingPhrase }}</span>
</div>
