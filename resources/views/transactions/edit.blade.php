<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative">
        
        <header class="flex justify-between items-center mb-6 pt-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">Edit Transaksi</h1>
            <a href="{{ route('transactions.index') }}" wire:navigate class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#262626] flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </a>
        </header>

        <div class="flex bg-[#1A1A1A] rounded-xl p-1.5 mb-6 border border-[#262626] justify-center opacity-80 pointer-events-none">
            <button class="w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-[#262626] text-[#FCA5FF] shadow-md border border-[#333]">
                Tipe: {{ $transaction->type->name ?? 'Transfer' }}
            </button>
        </div>

        @if ($errors->any())
            <div class="mb-5 p-4 bg-red-900/30 border border-red-500/50 text-red-200 rounded-xl text-xs font-medium">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('transactions.update', $transaction) }}" method="POST" id="trxForm" class="space-y-4">
            @csrf
            @method('PUT')
            
            {{-- 1. INPUT NOMINAL FORMATTED --}}
            <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-5 text-center relative shadow-inner group">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Nominal (Rp)</label>
                <input type="text" id="display_amount" inputmode="numeric" required 
                    value="{{ number_format($transaction->amount, 0, ',', '.') }}"
                    class="w-full bg-transparent border-none text-white text-center text-4xl font-bold placeholder-gray-600 focus:ring-0 p-0 focus:outline-none caret-[#FCA5FF]">
                <input type="hidden" name="amount" id="raw_amount" value="{{ (int)$transaction->amount }}">
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- 2. TANGGAL --}}
                <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-3 shadow-sm">
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Tanggal</label>
                    <input type="date" name="date" value="{{ $transaction->date }}" required class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0" style="color-scheme: dark;">
                </div>
                
                {{-- 3. KATEGORI (BOTTOM SHEET) --}}
                <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-3 shadow-sm cursor-pointer active:scale-95 transition-transform" onclick="openBottomSheet('categoryModal')">
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Kategori</label>
                    <div id="catLabelContainer" class="flex items-center justify-between px-1">
                        <span id="catLabel" class="text-sm font-bold text-white truncate">
                            {{ $transaction->category->category_name }}
                        </span>
                        <svg class="w-4 h-4 text-[#FCA5FF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input type="hidden" name="category_id" id="category_id" value="{{ $transaction->category_id }}">
                </div>
            </div>

            {{-- 4. DOMPET (BOTTOM SHEET) --}}
            <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-4 shadow-inner space-y-3">
                <div id="sourceDiv" class="{{ $transaction->type->name === 'Income' ? 'hidden' : 'block' }}">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Dari Dompet</label>
                    <div class="w-full bg-[#262626] text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-[#333]" onclick="openBottomSheet('walletModal', 'source')">
                        <span id="sourceWalletLabel" class="font-bold truncate">{{ $transaction->sourceWallet->name }}</span>
                        <svg class="w-4 h-4 text-[#FCA5FF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input type="hidden" name="source_wallet_id" id="source_wallet_id" value="{{ $transaction->source_wallet_id }}">
                </div>
                
                <div id="destDiv" class="{{ $transaction->type->name === 'Expense' ? 'hidden' : 'block' }}">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Ke Dompet</label>
                    <div class="w-full bg-[#262626] text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-[#333]" onclick="openBottomSheet('walletModal', 'dest')">
                        <span id="destWalletLabel" class="font-bold truncate">{{ $transaction->destinationWallet->name }}</span>
                        <svg class="w-4 h-4 text-[#FCA5FF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input type="hidden" name="destination_wallet_id" id="destination_wallet_id" value="{{ $transaction->destination_wallet_id }}">
                </div>
            </div>

            @php
                $isDebt = $transaction->type->name === 'Debt';
                $isReceivable = $transaction->type->name === 'Receivable';
            @endphp

            @if($isDebt || $isReceivable)
                <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-3 shadow-sm mt-3">
                    <label class="block text-[9px] font-bold text-[#FCA5FF] uppercase tracking-widest mb-1.5 ml-1">
                        {{ $isDebt ? 'Pelaku (Pemberi Hutang)' : 'Korban (Yang Ngutang)' }}
                    </label>
                    <input type="text" name="subject" value="{{ $transaction->subject !== '-' ? $transaction->subject : '' }}" required
                        class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0">
                </div>
            @else
                <input type="hidden" name="subject" value="-">
            @endif

            <div class="pt-2">
                <input type="text" name="notes" value="{{ $transaction->notes }}" placeholder="Catatan tambahan (opsional)" 
                    class="w-full bg-[#1A1A1A] border border-[#262626] text-white rounded-xl p-4 text-sm placeholder-gray-600 focus:border-[#FCA5FF] focus:ring-1 focus:ring-[#FCA5FF]">
            </div>

            <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-all mt-6">
                Update Transaksi
            </button>
        </form>

        <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="mt-4" onsubmit="return confirm('Hapus transaksi ini secara permanen?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full bg-transparent border border-red-900/50 text-red-500 font-bold text-sm uppercase tracking-widest py-4 rounded-xl hover:bg-red-900/20 active:scale-95 transition-all">
                Hapus Transaksi
            </button>
        </form>
    </div>

    {{-- MODALS --}}
    <div id="overlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-[50] hidden transition-opacity duration-300 opacity-0" onclick="closeAllBottomSheets()"></div>

    <div id="categoryModal" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-[#121212] rounded-t-[2rem] border-t border-x border-[#262626] z-[60] transform translate-y-full transition-transform duration-300 ease-out pb-safe">
        <div class="p-5 flex flex-col max-h-[80vh]">
            <div class="w-12 h-1.5 bg-[#333] rounded-full mx-auto mb-4 cursor-pointer" onclick="closeAllBottomSheets()"></div>
            <h3 class="text-sm font-bold text-[#FCA5FF] mb-4 uppercase tracking-widest text-center">Pilih Kategori</h3>
            <div id="categoryList" class="overflow-y-auto no-scrollbar space-y-2 pb-6"></div>
        </div>
    </div>

    <div id="walletModal" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-[#121212] rounded-t-[2rem] border-t border-x border-[#262626] z-[60] transform translate-y-full transition-transform duration-300 ease-out pb-safe">
        <div class="p-5 flex flex-col max-h-[80vh]">
            <div class="w-12 h-1.5 bg-[#333] rounded-full mx-auto mb-4 cursor-pointer" onclick="closeAllBottomSheets()"></div>
            <h3 class="text-sm font-bold text-[#FCA5FF] mb-4 uppercase tracking-widest text-center">Pilih Dompet</h3>
            <div id="walletList" class="overflow-y-auto no-scrollbar space-y-2 pb-6"></div>
        </div>
    </div>

<script>
    function isImagePath(str) {
        if (!str) return false;
        return str.includes('.png') || str.includes('.jpg') || str.includes('.jpeg') || str.includes('.webp') || str.includes('/');
    }

    window.openBottomSheet = function(id, mode = null) {
        document.getElementById('overlay').classList.remove('hidden');
        setTimeout(() => document.getElementById('overlay').classList.add('opacity-100'), 10);
        document.getElementById(id).classList.remove('translate-y-full');
        if (id === 'categoryModal') renderCategories();
        if (id === 'walletModal') renderWallets(mode);
    };

    window.closeAllBottomSheets = function() {
        document.getElementById('overlay').classList.remove('opacity-100');
        setTimeout(() => document.getElementById('overlay').classList.add('hidden'), 300);
        document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('translate-y-full'));
    };

    document.addEventListener('livewire:navigated', () => {
        window.allCategories = @json($categories);
        window.allWallets = @json($wallets);
        window.systemWallets = @json($systemWallets);
        window.currentType = "{{ $transaction->type->name }}";
        
        const displayAmount = document.getElementById('display_amount');
        const rawAmount = document.getElementById('raw_amount');
        if (displayAmount) {
            displayAmount.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, ''); 
                rawAmount.value = val; 
                this.value = val ? new Intl.NumberFormat('id-ID').format(parseInt(val, 10)) : '';
            });
        }
    });

    function renderCategories() {
        const list = document.getElementById('categoryList');
        list.innerHTML = '';
        // Filter kategori berdasarkan tipe transaksi saat ini
        const filtered = window.allCategories.filter(cat => cat.type.name === window.currentType);
        
        filtered.forEach(cat => {
            const iconContent = isImagePath(cat.icon) 
                ? `<img src="/storage/${cat.icon}" class="w-full h-full object-cover rounded-xl">`
                : cat.icon || '📄';
            list.innerHTML += `
                <div onclick="selectCategory('${cat.id}', '${cat.category_name}', '${cat.icon || '📄'}')" 
                    class="bg-[#1A1A1A] border border-[#262626] p-4 rounded-xl flex items-center gap-4 cursor-pointer hover:bg-[#202020] active:scale-95 transition-all">
                    <div class="w-12 h-12 bg-[#262626] rounded-xl flex items-center justify-center text-xl border border-[#333] shrink-0 overflow-hidden">${iconContent}</div>
                    <span class="text-sm font-bold text-white">${cat.category_name}</span>
                </div>`;
        });
    }

    window.selectCategory = function(id, name, icon) {
        document.getElementById('category_id').value = id;
        document.getElementById('catLabel').innerText = name;
        closeAllBottomSheets();
    };

    function renderWallets(mode) {
    window.currentWalletModalMode = mode;
    const list = document.getElementById('walletList');
    list.innerHTML = '';
    
    const type = window.currentType; // Expense, Income, Transfer, dll
    let filtered = [];

    // LOGIKA FILTER DOMPET BIAR GAK BOCOR
    if (type === 'Expense') {
        // Source harus aset kita, Dest harus sistem (Merchant)
        filtered = (mode === 'source') 
            ? window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type))
            : window.systemWallets.filter(w => w.name.toLowerCase().includes('merchant'));
    } 
    else if (type === 'Income') {
        // Source harus sistem (External), Dest harus aset kita
        filtered = (mode === 'source')
            ? window.systemWallets.filter(w => w.name.toLowerCase().includes('external'))
            : window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
    }
    else if (type === 'Transfer') {
        // Dua-duanya harus aset kita (bukan sistem)
        filtered = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
    }
    else if (type === 'Debt') {
        // Source harus dompet Hutang (Sistem), Dest harus aset kita
        filtered = (mode === 'source')
            ? window.systemWallets.filter(w => w.name.toLowerCase().includes('hutang'))
            : window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
    }
    else if (type === 'Receivable') {
        // Source harus aset kita, Dest harus dompet Piutang (Sistem)
        filtered = (mode === 'source')
            ? window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type))
            : window.systemWallets.filter(w => w.name.toLowerCase().includes('piutang'));
    }

    // Render hasil filter
    filtered.forEach(w => {
        const iconContent = isImagePath(w.icon) 
            ? `<img src="/storage/${w.icon}" class="w-full h-full object-cover rounded-xl">`
            : w.icon || '💳';
            
        list.innerHTML += `
            <div onclick="selectWallet('${w.id}', '${w.name}', '${w.icon || '💳'}')" 
                class="bg-[#1A1A1A] border border-[#262626] p-4 rounded-xl flex items-center gap-4 cursor-pointer hover:bg-[#202020] active:scale-95 transition-all">
                <div class="w-12 h-12 bg-[#262626] rounded-xl flex items-center justify-center text-xl border border-[#333] shrink-0 overflow-hidden">${iconContent}</div>
                <div>
                    <span class="text-sm font-bold text-white">${w.name}</span>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">${w.group_type}</p>
                </div>
            </div>`;
    });
}

    window.selectWallet = function(id, name, icon) {
        const isSource = window.currentWalletModalMode === 'source';
        document.getElementById(isSource ? 'source_wallet_id' : 'destination_wallet_id').value = id;
        document.getElementById(isSource ? 'sourceWalletLabel' : 'destWalletLabel').innerText = name;
        closeAllBottomSheets();
    };
</script>
<style> .no-scrollbar::-webkit-scrollbar { display: none; } </style>
</x-app-layout>