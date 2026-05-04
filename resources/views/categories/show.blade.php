<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative">
        
        {{-- Glow Background Ambient --}}
        <div class="fixed top-[-5%] left-[50%] -translate-x-1/2 w-[100%] max-w-md h-[400px] pointer-events-none z-0"></div>

        {{-- Header Navigation --}}
        <header class="flex justify-between items-center mb-8 pt-4 relative z-10">
            {{-- TOMBOL CLOSE DINAMIS (Sistem Baru) --}}
            <button type="button" onclick="handleClose()" class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#262626] flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            @if(!$isSystem)
            <a href="{{ route('categories.edit', $category) }}" wire:navigate class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#262626] flex items-center justify-center text-[#FCA5FF] active:scale-95 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </a>
            @endif
        </header>

        @php
            $rawIcon = $category->icon ?? '📁';
            $isImage = \Illuminate\Support\Str::contains($rawIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
        @endphp

        {{-- Profil Kategori Card --}}
        <div class="flex flex-col items-center mb-10 relative z-10">
            <div class="w-24 h-24 rounded-xl bg-[#1A1A1A] border border-[#262626] flex items-center justify-center text-5xl shadow-2xl mb-5 overflow-hidden relative p-1">
                @if($isImage)
                    <img src="{{ asset('storage/' . $rawIcon) }}" class="w-full h-full object-cover rounded-xl">
                @else
                    <span class="relative z-10">{{ $rawIcon }}</span>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            </div>
            
            <div class="text-center">
                <h1 class="text-3xl font-black text-white tracking-tight leading-none mb-2">{{ $category->category_name }}</h1>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#1A1A1A] border border-[#262626]">
                    <span class="w-1.5 h-1.5 rounded-full {{ $category->type->name == 'Income' ? 'bg-green-400 shadow-[0_0_8px_#4ade80]' : 'bg-gray-500' }}"></span>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">{{ $category->type->name == 'Income' ? 'Pemasukan' : 'Pengeluaran' }}</p>
                </div>
            </div>
        </div>

        {{-- Stats Ringkas --}}
        <div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] border border-white/10 rounded-xl p-7 text-center mb-10 shadow-2xl relative overflow-hidden group z-10">
            <div class="absolute top-0 left-0 w-full h-1 bg-[#FCA5FF]/20"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-[0.2em] mb-2 opacity-60">Total Akumulasi</p>
            <div class="flex items-baseline justify-center gap-1.5">
                <span class="text-sm font-bold text-gray-600">Rp</span>
                <h2 class="text-3xl font-black text-white tracking-tight">{{ number_format($totalUsage, 0, ',', '.') }}</h2>
            </div>
        </div>

        {{-- Histori Transaksi --}}
        <div class="space-y-4 relative z-10">
            <div class="flex items-center justify-between px-1 mb-2">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Riwayat Transaksi</h3>
                <span class="text-xs font-bold text-gray-600 bg-[#1A1A1A] px-2 py-0.5 rounded-xl border border-[#262626]">{{ count($transactions) }} Record</span>
            </div>
            
            @forelse($transactions as $trx)
                @php
                    $typeColor = match($trx->type->name) { 
                        'Income' => 'text-green-400 bg-green-400/10 border-green-400/20', 
                        'Expense' => 'text-gray-400 bg-gray-400/10 border-gray-400/20', 
                        'Transfer' => 'text-blue-400 bg-blue-400/10 border-blue-400/20', 
                        'Debt' => 'text-[#E5D07E] bg-[#E5D07E]/10 border-[#E5D07E]/20', 
                        'Receivable' => 'text-[#FCA5FF] bg-[#FCA5FF]/10 border-[#FCA5FF]/20', 
                        default => 'text-gray-500' 
                    };
                    $catIcon = $trx->category->icon ?? '📄';
                    $catIsImage = \Illuminate\Support\Str::contains($catIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
                @endphp

                <button type="button" 
                    onclick="openDetailModal(this)"
                    data-id="{{ $trx->id }}" 
                    data-type="{{ $trx->type->name }}" 
                    data-amount="{{ number_format($trx->amount, 0, ',', '.') }}" 
                    data-category="{{ $trx->category->category_name }}" 
                    data-icon="{{ $catIcon }}" 
                    data-is-image="{{ $catIsImage ? 'true' : 'false' }}" 
                    data-icon-url="{{ asset('storage/' . $catIcon) }}"
                    data-date="{{ \Carbon\Carbon::parse($trx->date)->translatedFormat('d M Y') }}" 
                    data-time="{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}"
                    data-source="{{ $trx->sourceWallet->name }}" 
                    data-dest="{{ $trx->destinationWallet->name }}" 
                    data-subject="{{ $trx->subject }}" 
                    data-notes="{{ $trx->notes }}"
                    class="w-full text-left bg-[#1A1A1A] p-4 rounded-xl border border-[#262626] flex justify-between items-center active:scale-[0.98] transition-all shadow-sm relative overflow-hidden group">
                    
                    <div class="flex-1 min-w-0 pr-3 relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="text-[9px] font-black text-gray-500 uppercase tracking-tighter italic">
                                {{ \Carbon\Carbon::parse($trx->date)->translatedFormat('d M Y') }}
                            </p>
                            <span class="w-1 h-1 bg-gray-700 rounded-full"></span>
                            <p class="text-[9px] text-gray-600 font-bold uppercase">{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}</p>
                        </div>
                        
                        <div class="mb-2">
                            <p class="text-sm font-bold text-white truncate">{{ $trx->notes ?? 'Tanpa catatan' }}</p>
                            @if($trx->subject && $trx->subject !== '-')
                                <p class="text-xs text-[#E5D07E] font-bold mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ $trx->subject }}
                                </p>
                            @endif
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5 bg-[#262626] px-2 py-1 rounded-xl border border-[#333]">
                                <span class="text-[8px] font-bold text-gray-400 uppercase truncate max-w-[60px]">{{ $trx->sourceWallet->name }}</span>
                                <svg class="w-2.5 h-2.5 text-[#FCA5FF]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                <span class="text-[8px] font-bold text-white uppercase truncate max-w-[60px]">{{ $trx->destinationWallet->name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right shrink-0 relative z-10">
                        <p class="text-base font-black {{ $category->type->name == 'Income' ? 'text-green-400' : 'text-white' }} mb-1.5">
                            {{ $category->type->name == 'Income' ? '+' : '-' }}{{ number_format($trx->amount, 0, ',', '.') }}
                        </p>
                        <span class="inline-block text-[7px] uppercase tracking-widest font-black px-1.5 py-0.5 rounded border {{ $typeColor }}">
                            {{ $trx->type->name }}
                        </span>
                    </div>
                </button>
            @empty
                <div class="text-center py-16 bg-[#1A1A1A] rounded-xl border-2 border-dashed border-[#262626]">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Belum ada transaksi</p>
                </div>
            @endforelse
        </div>
    </div>
    
    <x-transaction-detail-modal />
    <x-bottom-nav />

    <script>
        // Backup handleClose jika belum ada di file lain
        if (typeof handleClose !== 'function') {
            function handleClose() {
                if (window.history.length > 1 && document.referrer !== "") {
                    window.history.back();
                } else {
                    window.location.href = "{{ route('dashboard') }}"; 
                }
            }
        }
    </script>
</x-app-layout>