<x-app-layout>
    {{-- Latar Belakang Glow Ambient --}}
    <div class="fixed top-0 left-[50%] -translate-x-1/2 w-[100%] max-w-md h-[400px] bg-gradient-to-b from-[#FCA5FF]/5 to-transparent pointer-events-none z-0"></div>

    <div class="p-5 pb-32 max-w-md mx-auto relative z-10 min-h-screen">
        
<header class="mb-10 pt-4 animate-fade-in-up">
    {{-- Atas: Judul & Stats --}}
    <div class="flex justify-between items-end mb-8 px-1">
        <div>
            <p class="text-[10px] text-[#FCA5FF] font-black uppercase tracking-[0.3em] mb-1 opacity-80">✨ Collection</p>
            <h1 class="text-3xl font-black text-white tracking-tighter leading-none">Vault <span class="text-gray-500">Kategori</span></h1>
        </div>
        
        <div class="flex flex-col items-end">

             <span class="text-[7px] text-gray-500 font-bold uppercase tracking-[0.2em] leading-none">Total</span>
            <div class="flex items-center gap-1.5 mb-0.5">
                <div class="w-1.5 h-1.5 rounded-full bg-[#FCA5FF] shadow-[0_0_8px_#FCA5FF]"></div>
                <span class="text-lg font-black text-white leading-none">{{ $totalCategories }}</span>
            </div>
           
        </div>
    </div>

    {{-- Tombol Action: Tambah Kategori --}}
    <a href="{{ route('categories.create') }}" wire:navigate 
       class="relative w-full h-16 bg-gradient-to-br from-[#1E1E1E] to-[#121212] border border-white/5 rounded-[1.5rem] flex items-center justify-between px-6 active:scale-[0.97] transition-all group overflow-hidden shadow-2xl">
        
        {{-- Glassy Overlay & Animated Gradient --}}
        <div class="absolute inset-0 bg-gradient-to-r from-[#FCA5FF]/0 via-[#FCA5FF]/5 to-[#FCA5FF]/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out"></div>
        
        <div class="relative z-10 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#FCA5FF] flex items-center justify-center shadow-[0_0_15px_rgba(252,165,255,0.4)] group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-[#121212]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="text-[11px] font-black text-white uppercase tracking-[0.15em]">Tambah Kategori</span>
                <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Organisir pengeluaran baru</span>
            </div>
        </div>

        <div class="relative z-10 flex items-center">
            <div class="w-8 h-8 rounded-full border border-white/10 flex items-center justify-center group-hover:border-[#FCA5FF]/50 transition-colors">
                <svg class="w-4 h-4 text-gray-500 group-hover:text-[#FCA5FF] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>
        
        {{-- Decorative Mesh Background --}}
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-[#FCA5FF]/5 blur-3xl rounded-full"></div>
    </a>
</header>
        {{-- Logika Sorting Manual untuk 5 Grup --}}
        @php
            $desiredOrder = ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable'];
            
            $sortedGroups = collect($groupedCategories)->sortBy(function ($value, $key) use ($desiredOrder) {
                $pos = array_search($key, $desiredOrder);
                return $pos === false ? 999 : $pos;
            });
        @endphp

        @forelse($sortedGroups as $typeName => $categories)
            @php
                // Tentukan Tema (Warna Teks, Glow, dan BG) berdasarkan tipe
                $theme = match($typeName) {
                    'Income' => ['text' => 'text-green-400', 'bg' => 'bg-green-500', 'glow' => 'group-hover:shadow-[0_0_15px_rgba(74,222,128,0.2)]', 'border' => 'group-hover:border-green-500/50'],
                    'Expense' => ['text' => 'text-gray-300', 'bg' => 'bg-gray-400', 'glow' => 'group-hover:shadow-[0_0_15px_rgba(156,163,175,0.2)]', 'border' => 'group-hover:border-gray-500/50'],
                    'Transfer' => ['text' => 'text-blue-400', 'bg' => 'bg-blue-500', 'glow' => 'group-hover:shadow-[0_0_15px_rgba(59,130,246,0.2)]', 'border' => 'group-hover:border-blue-500/50'],
                    'Debt' => ['text' => 'text-[#E5D07E]', 'bg' => 'bg-[#E5D07E]', 'glow' => 'group-hover:shadow-[0_0_15px_rgba(229,208,126,0.2)]', 'border' => 'group-hover:border-[#E5D07E]/50'],
                    'Receivable' => ['text' => 'text-[#FCA5FF]', 'bg' => 'bg-[#FCA5FF]', 'glow' => 'group-hover:shadow-[0_0_15px_rgba(252,165,255,0.2)]', 'border' => 'group-hover:border-[#FCA5FF]/50'],
                    default => ['text' => 'text-white', 'bg' => 'bg-white', 'glow' => 'group-hover:shadow-[0_0_15px_rgba(255,255,255,0.2)]', 'border' => 'group-hover:border-white/50']
                };
                
                // Tentukan teks judul berdasarkan tipe
                $headerText = match($typeName) {
                    'Income' => 'Pemasukan',
                    'Expense' => 'Pengeluaran',
                    'Transfer' => 'Transfer',
                    'Debt' => 'Kategori Hutang',
                    'Receivable' => 'Kategori Piutang',
                    default => 'Lainnya'
                };
            @endphp
            
            <div class="mb-10 animate-fade-in-up" style="animation-delay: {{ $loop->index * 100 }}ms;">
                
                {{-- HEADER GRUP KATEGORI --}}
                <div class="flex items-center gap-3 mb-5 px-1">
                    <div class="w-1.5 h-1.5 rounded-full {{ $theme['bg'] }}"></div>
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.2em] {{ $theme['text'] }}">
                        {{ $headerText }}
                    </h2>
                    <div class="flex-1 h-px bg-gradient-to-r from-white/10 to-transparent"></div>
                </div>

                {{-- GRID CARDS --}}
                <div class="grid grid-cols-3 gap-3">
                    @foreach($categories as $category)
                        @php
                            // LOGIKA CEK GAMBAR 
                            $rawIcon = $category->icon ?? '📁';
                            $isImage = \Illuminate\Support\Str::contains($rawIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
                        @endphp

                        <a href="{{ route('categories.show', $category) }}" wire:navigate 
                           class="relative group bg-gradient-to-br from-[#1E1E1E] to-[#121212] border border-white/5 rounded-[1.8rem] p-4 flex flex-col items-center justify-center text-center active:scale-95 transition-all duration-300 {{ $theme['glow'] }} {{ $theme['border'] }}">
                            
                            {{-- Kotak Icon / Gambar --}}
                            <div class="w-12 h-12 rounded-[1rem] bg-gradient-to-br from-[#2A2A2A] to-[#1A1A1A] flex items-center justify-center text-2xl border border-white/5 shrink-0 shadow-inner overflow-hidden p-0.5 mb-2.5 transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-1">
                                @if($isImage)
                                    <img src="{{ asset('storage/' . $rawIcon) }}" class="w-full h-full object-cover rounded-[0.8rem]">
                                @else
                                    <span class="drop-shadow-md">{{ $rawIcon }}</span>
                                @endif
                            </div>
                            
                            {{-- Nama Kategori --}}
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest line-clamp-2 leading-tight group-hover:text-gray-200 transition-colors">
                                {{ $category->category_name }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            {{-- EMPTY STATE --}}
            <div class="text-center py-20 bg-[#1A1A1A]/50 backdrop-blur-sm rounded-[2.5rem] border border-dashed border-[#333] mt-8 animate-fade-in-up flex flex-col items-center">
                <div class="w-16 h-16 bg-[#262626] rounded-2xl flex items-center justify-center text-3xl mb-4 border border-[#333] shadow-inner">🗄️</div>
                <p class="text-xs font-bold text-white uppercase tracking-widest">Vault Masih Kosong</p>
                <p class="text-[10px] font-medium text-gray-500 mt-2 max-w-[200px] leading-relaxed">Buat kategori pertamamu sekarang untuk mulai mencatat keuangan.</p>
            </div>
        @endforelse

    </div>

    <x-bottom-nav />

    {{-- CSS TAMBAHAN UNTUK ANIMASI --}}
    <style>
        @keyframes fade-in-up { 
            0% { opacity: 0; transform: translateY(15px); } 
            100% { opacity: 1; transform: translateY(0); } 
        }
        .animate-fade-in-up { 
            animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
            opacity: 0; 
        }
    </style>
    <x-create-transaction />
</x-app-layout>