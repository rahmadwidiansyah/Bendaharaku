<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative">
        
        <header class="flex justify-between items-center mb-6 pt-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">Detail Dompet</h1>
            <a href="{{ route('dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#262626] flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </header> 

        @php
            $walletIcon = $wallet->icon ?? '💳';
            $isWalletImage = \Illuminate\Support\Str::contains($walletIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
        @endphp

        {{-- Card Dompet Utama --}}
        <div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] border border-white/5 rounded-[2.5rem] p-7 text-center mb-10 shadow-2xl relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#FCA5FF] opacity-[0.05] rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            
            <div class="w-20 h-20 bg-[#262626] rounded-[2rem] mx-auto flex items-center justify-center text-4xl border border-[#333] mb-4 shadow-inner overflow-hidden p-1">
                @if($isWalletImage)
                    <img src="{{ asset('storage/' . $walletIcon) }}" class="w-full h-full object-cover rounded-[1.5rem]">
                @else
                    {{ $walletIcon }}
                @endif
            </div>

            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-1">{{ $wallet->name }}</p>
            <h2 class="text-3xl font-black text-white tracking-tight mb-6">Rp {{ number_format($wallet->balance, 0, ',', '.') }}</h2>
            
            <a href="{{ route('wallets.edit', $wallet) }}" wire:navigate class="inline-block bg-[#1A1A1A] border border-[#333] text-[#FCA5FF] text-[10px] font-bold px-6 py-2.5 rounded-xl uppercase tracking-widest active:scale-95 transition-all">
                ✏️ Edit Dompet
            </a>
        </div>

        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1 text-center">Mutasi Terakhir</h2>

        {{-- List Mutasi --}}
        <div class="space-y-4">
            @forelse($transactions as $trx)
                @php
                    $trxIcon = $trx->category->icon ?? '📄';
                    $isTrxImage = \Illuminate\Support\Str::contains($trxIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
                    
                    $typeColor = match($trx->type->name) { 
                        'Income' => 'text-green-400 bg-green-400/10 border-green-400/20', 
                        'Expense' => 'text-gray-400 bg-gray-400/10 border-gray-400/20', 
                        'Transfer' => 'text-blue-400 bg-blue-400/10 border-blue-400/20', 
                        'Debt' => 'text-[#E5D07E] bg-[#E5D07E]/10 border-[#E5D07E]/20', 
                        'Receivable' => 'text-[#FCA5FF] bg-[#FCA5FF]/10 border-[#FCA5FF]/20', 
                        default => 'text-gray-500' 
                    };
                @endphp

                <button type="button" 
                    onclick="openDetailModal(this)"
                    data-id="{{ $trx->id }}" 
                    data-type="{{ $trx->type->name }}" 
                    data-amount="{{ number_format($trx->amount, 0, ',', '.') }}" 
                    data-category="{{ $trx->category->category_name }}" 
                    data-icon="{{ $trxIcon }}" 
                    data-is-image="{{ $isTrxImage ? 'true' : 'false' }}" 
                    data-icon-url="{{ asset('storage/' . $trxIcon) }}"
                    data-date="{{ \Carbon\Carbon::parse($trx->date)->translatedFormat('d M Y') }}" 
                    data-time="{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}"
                    data-source="{{ $trx->sourceWallet->name }}" 
                    data-dest="{{ $trx->destinationWallet->name }}" 
                    data-subject="{{ $trx->subject }}" 
                    data-notes="{{ $trx->notes }}"
                    class="w-full text-left bg-[#1A1A1A] p-4 rounded-[1.8rem] border border-[#262626] flex justify-between items-center active:scale-[0.98] transition-all shadow-sm relative overflow-hidden group">
                    
                    <div class="flex items-center gap-3 flex-1 min-w-0 relative z-10">
                        {{-- IKON KATEGORI --}}
                        <div class="w-11 h-11 rounded-xl bg-[#262626] flex items-center justify-center text-xl border border-[#333] shrink-0 overflow-hidden p-0.5">
                            @if($isTrxImage)
                                <img src="{{ asset('storage/' . $trxIcon) }}" class="w-full h-full object-cover rounded-lg">
                            @else
                                {{ $trxIcon }}
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-sm font-bold text-white truncate">{{ $trx->category->category_name }}</p>
                            </div>
                            
                            {{-- ALUR DOMPET --}}
                            <div class="flex items-center gap-1.5 opacity-60">
                                <span class="text-[8px] font-bold text-gray-400 uppercase truncate max-w-[50px]">{{ $trx->sourceWallet->name }}</span>
                                <svg class="w-2.5 h-2.5 text-[#FCA5FF]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                <span class="text-[8px] font-bold text-white uppercase truncate max-w-[50px]">{{ $trx->destinationWallet->name }}</span>
                            </div>
                            
                            <p class="text-[8px] text-gray-600 font-bold uppercase mt-1">
                                {{ \Carbon\Carbon::parse($trx->date)->translatedFormat('d M') }} • {{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="text-right shrink-0 relative z-10 ml-2">
                        @if($trx->destination_wallet_id == $wallet->id)
                            <p class="text-sm font-black text-green-400">+{{ number_format($trx->amount, 0, ',', '.') }}</p>
                        @else
                            <p class="text-sm font-black text-white">-{{ number_format($trx->amount, 0, ',', '.') }}</p>
                        @endif
                        <span class="inline-block text-[7px] uppercase tracking-widest font-black px-1.5 py-0.5 rounded border {{ $typeColor }} mt-1">
                            {{ $trx->type->name }}
                        </span>
                    </div>
                </button>
            @empty
                <div class="text-center py-12 bg-[#1A1A1A] rounded-[2.5rem] border-2 border-dashed border-[#262626]">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Belum ada mutasi</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $transactions->links() }}
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <x-transaction-detail-modal />

    <x-bottom-nav />
</x-app-layout>