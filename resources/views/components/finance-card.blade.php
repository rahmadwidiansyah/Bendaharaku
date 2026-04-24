@props(['title', 'amount', 'titleColor', 'iconColor'])

<div class="bg-[#1A1A1A] p-4 rounded-3xl border border-[#262626] relative overflow-hidden group">
    <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-full bg-[#262626] flex items-center justify-center text-sm shadow-inner border border-[#333] {{ $iconColor }} shrink-0">
            {{ $slot }}
        </div>
        <p class="text-[11px] font-bold text-gray-400 uppercase">{{ $title }}</p>
    </div>
    <p class="text-[17px] font-bold {{ $titleColor }} tracking-tight">Rp {{ number_format($amount, 0, ',', '.') }}</p>
</div>