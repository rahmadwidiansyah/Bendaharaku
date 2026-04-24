@php
    // 🛠️ SETTING LOGO APLIKASI DI SINI 🛠️
    // Isi dengan emoji (contoh: '💎', '💰') ATAU path file gambar (contoh: 'images/logo.png')
    $appIcon = '💎';
    
    // Logika deteksi otomatis: apakah ini file gambar?
    $isImage = \Illuminate\Support\Str::contains($appIcon, ['.png', '.jpg', '.jpeg', '.webp', '.svg', '/']);
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-[#1A1A1A] border border-[#262626] shadow-inner overflow-hidden']) }}>
    @if($isImage)
        {{-- Kalau terdeteksi sebagai gambar/foto --}}
        <img src="{{ asset($appIcon) }}" alt="Logo Aplikasi" class="w-2/3 h-2/3 object-contain drop-shadow-[0_0_10px_rgba(252,165,255,0.3)]">
    @else
        {{-- Kalau terdeteksi sebagai Emoji/Teks --}}
        <span class="text-4xl drop-shadow-[0_0_10px_rgba(252,165,255,0.2)]">{{ $appIcon }}</span>
    @endif
</div>