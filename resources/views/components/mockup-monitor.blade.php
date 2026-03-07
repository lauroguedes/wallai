<div class="relative flex flex-col items-center w-full">
    {{-- Display Panel --}}
    <div class="relative z-10 w-full bg-base-300 rounded-[2rem] p-3 shadow-2xl border-2 border-primary flex flex-col ring-1 ring-white/10">
        {{-- Camera --}}
        <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-black rounded-full border border-base-content/20 flex items-center justify-center">
            <div class="w-1 h-1 bg-primary/50 rounded-full"></div>
        </div>
        {{-- Screen --}}
        <div class="flex-1 rounded-xl overflow-hidden relative border border-black/50 shadow-inner">
            {{ $slot }}
        </div>
    </div>
    {{-- Stand Neck --}}
    <div class="relative z-0 w-36 h-28 -mt-8 flex flex-col items-center">
        <div class="w-full h-full bg-gradient-to-b from-[#e5e7eb] to-[#9ca3af] border-l border-r border-[#9ca3af] relative shadow-lg">
            <div class="absolute top-0 w-full h-10 bg-gradient-to-b from-black/40 to-transparent"></div>
            <div class="absolute inset-y-0 left-4 w-3 bg-white/30 blur-sm"></div>
            <div class="absolute inset-y-0 right-4 w-3 bg-black/10 blur-sm"></div>
        </div>
    </div>
    {{-- Stand Base --}}
    <div class="relative z-0 flex flex-col items-center -mt-1">
        <div class="w-80 h-5 bg-gradient-to-b from-[#f3f4f6] to-[#d1d5db] rounded-t-2xl border-t border-[#ffffff] shadow-sm relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-1 bg-white/60"></div>
        </div>
        <div class="w-80 h-1.5 bg-[#9ca3af] rounded-b-sm shadow-2xl border-b border-[#6b7280]"></div>
    </div>
</div>
