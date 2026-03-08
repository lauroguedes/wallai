@props(['jobId', 'isLoading', 'size' => 'w-16 h-16', 'keyPrefix' => 'pending'])

<button wire:click="selectPendingJob" wire:key="{{ $keyPrefix }}-{{ $jobId }}"
        class="shrink-0 {{ $size }} rounded-lg overflow-hidden border-2 transition-all
               {{ $isLoading ? 'border-primary ring-2 ring-primary/30' : 'border-base-300' }} skeleton">
</button>
