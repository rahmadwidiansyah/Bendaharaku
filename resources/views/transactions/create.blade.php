<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative">
        
        <header class="flex justify-between items-center mb-6 pt-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">Catat Transaksi</h1>
            <button type="button" onclick="handleClose()" class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </header>

        {{-- TABS TIPE --}}
        <div class="grid grid-cols-3 gap-2 mb-2">
            <button type="button" id="tabExpense" onclick="setType('Expense')" class="w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-red-500 transition-all">Pengeluaran</button>
            <button type="button" id="tabIncome" onclick="setType('Income')" class="w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-green-500 transition-all">Pemasukan</button>
            <button type="button" id="tabTransfer" onclick="setType('Transfer')" class="w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-purple-500 transition-all">Transfer</button>
        </div>
        <div class="grid grid-cols-2 gap-2 mb-6">
            <button type="button" id="tabDebt" onclick="setType('Debt')" class="w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-orange-500 transition-all">Hutang</button>
            <button type="button" id="tabReceivable" onclick="setType('Receivable')" class="w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-yellow-500 hover:text-white border border-transparent transition-all">Piutang</button>
        </div>

        <form action="{{ route('transactions.store') }}" method="POST" id="trxForm" class="space-y-4" onsubmit="return validateForm()">
            @csrf
            
            {{-- INPUT NOMINAL --}}
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-5 text-center relative shadow-inner group">
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Nominal (Rp)</label>
                <input type="text" id="display_amount" inputmode="numeric" required placeholder="0"
                    class="w-full bg-transparent border-none text-white text-center text-4xl font-bold placeholder-gray-600 focus:ring-0 p-0 focus:outline-none caret-purple-500">
                <input type="hidden" name="amount" id="raw_amount">
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-12 h-1 bg-purple-500 rounded-full opacity-0 group-focus-within:opacity-100 transition-opacity"></div>
            </div>

            {{-- TANGGAL & KATEGORI --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm relative">
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Tanggal</label>
                    <input type="date" id="dateInput" name="date" value="{{ date('Y-m-d') }}" required class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0" style="color-scheme: dark;">
                    <p id="dateError" class="absolute -bottom-5 left-2 text-[9px] text-red-500 hidden font-bold">Masa depan tidak diizinkan!</p>
                </div>
                
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm cursor-pointer active:scale-95 transition-transform" onclick="openBottomSheet('categoryModal')">
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Kategori</label>
                    <div class="flex items-center justify-between px-1">
                        <div id="catPreview" class="flex items-center gap-2 truncate">
                            <span id="catLabel" class="text-sm font-bold text-white truncate">-- Pilih --</span>
                        </div>
                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input type="hidden" name="category_id" id="category_id" required>
                </div>
            </div>

            {{-- DOMPET SOURCE & DESTINATION --}}
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 shadow-inner flex flex-col gap-3">
                <div id="sourceDiv">
                    <label id="sourceLabel" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Dari Dompet</label>
                    <div class="w-full bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-white/10" onclick="openBottomSheet('walletModal', 'source')">
                        <span id="sourceWalletLabel" class="font-bold">-- Pilih Dompet --</span>
                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input type="hidden" name="source_wallet_id" id="source_wallet_id" required>
                </div>
                <div id="destDiv">
                    <label id="destLabel" class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Ke Dompet</label>
                    <div class="w-full bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-white/10" onclick="openBottomSheet('walletModal', 'dest')">
                        <span id="destWalletLabel" class="font-bold">-- Pilih Dompet --</span>
                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <input type="hidden" name="destination_wallet_id" id="destination_wallet_id" required>
                </div>
            </div>

            <div id="subjectDiv" style="display: none;" class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm mt-3">
                <label id="subjectLabel" class="block text-[9px] font-bold text-purple-500 uppercase tracking-widest mb-1.5 ml-1">Pihak Terkait</label>
                <input type="text" id="subjectInput" name="subject" placeholder="Nama..." class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0">
            </div>

            <div class="pt-2">
                <input type="text" name="notes" placeholder="Catatan tambahan (opsional)" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm placeholder-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
            </div>

            <button type="submit" class="w-full bg-gradient-to-br from-purple-500 to-purple-600 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-lg active:scale-95 transition-all mt-6">
                Simpan Transaksi
            </button>
        </form>
    </div>

    {{-- MODAL OVERLAY & BOTTOM SHEETS --}}
    <div id="overlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-[50] hidden transition-opacity duration-300 opacity-0" onclick="closeAllBottomSheets()"></div>

    <div id="categoryModal" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-gradient-to-br from-gray-900 to-gray-800 rounded-t-[2rem] border-t border-x border-white/10 z-[60] transform translate-y-full transition-transform duration-300 ease-out pb-safe">
        <div class="p-5 flex flex-col max-h-[80vh]">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-4 cursor-pointer" onclick="closeAllBottomSheets()"></div>
            <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Kategori</h3>
            <div id="categoryList" class="overflow-y-auto no-scrollbar space-y-2 pb-6"></div>
        </div>
    </div>

    <div id="walletModal" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-gradient-to-br from-gray-900 to-gray-800 rounded-t-[2rem] border-t border-x border-white/10 z-[60] transform translate-y-full transition-transform duration-300 ease-out pb-safe">
        <div class="p-5 flex flex-col max-h-[80vh]">
            <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-4 cursor-pointer" onclick="closeAllBottomSheets()"></div>
            <h3 id="walletModalTitle" class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Dompet</h3>
            <div id="walletList" class="overflow-y-auto no-scrollbar space-y-2 pb-6"></div>
        </div>
    </div>

<script>
    // 1. FUNGSI DASAR UI
    window.handleClose = function() {
        if (window.history.length > 1 && document.referrer !== "") { window.history.back(); } 
        else { window.location.href = "{{ route('dashboard') }}"; }
    };

    window.openBottomSheet = function(id, mode = null) {
        const overlay = document.getElementById('overlay');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.add('opacity-100'), 10); 
        document.getElementById(id).classList.remove('translate-y-full'); 
        if (id === 'categoryModal') renderCategories();
        if (id === 'walletModal') renderWallets(mode);
    };

    window.closeAllBottomSheets = function() {
        const overlay = document.getElementById('overlay');
        if (overlay) {
            overlay.classList.remove('opacity-100');
            setTimeout(() => overlay.classList.add('hidden'), 300); 
        }
        document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('translate-y-full'));
    };

    // 2. FUNGSI SELEKSI (KATEGORI & WALLET)
    window.selectCategory = function(id, name, icon) {
        document.getElementById('category_id').value = id;
        const label = document.getElementById('catLabel');
        const isImage = icon.includes('/') || icon.includes('.');
        
        if (isImage) {
            label.innerHTML = `<div class="flex items-center gap-2"><img src="/storage/${icon}" class="w-5 h-5 object-cover rounded-xl"><span>${name}</span></div>`;
        } else {
            label.innerText = icon + ' ' + name;
        }

        adjustWalletBySelection(name);
        closeAllBottomSheets();
    };

    window.selectWallet = function(id, name, icon) {
        const isSource = window.currentWalletModalMode === 'source';
        const input = document.getElementById(isSource ? 'source_wallet_id' : 'destination_wallet_id');
        const label = document.getElementById(isSource ? 'sourceWalletLabel' : 'destWalletLabel');
        const isImage = icon.includes('/') || icon.includes('.');
        
        input.value = id;
        if (isImage) {
            label.innerHTML = `<div class="flex items-center gap-2"><img src="/storage/${icon}" class="w-5 h-5 object-cover rounded-xl"><span>${name}</span></div>`;
        } else {
            label.innerText = icon + ' ' + name;
        }
        closeAllBottomSheets();
    };

    // 3. LOGIKA ARAH ALIRAN UANG (HUTANG/PIUTANG/TRANSFER)
    function adjustWalletBySelection(categoryName) {
        const sDiv = document.getElementById('sourceDiv'); 
        const dDiv = document.getElementById('destDiv');
        const sIn = document.getElementById('source_wallet_id'); 
        const dIn = document.getElementById('destination_wallet_id');
        const sLb = document.getElementById('sourceWalletLabel'); 
        const dLb = document.getElementById('destWalletLabel');
        
        const syH = window.systemWallets.find(w => w.name.toLowerCase().includes('hutang'));
        const syP = window.systemWallets.find(w => w.name.toLowerCase().includes('piutang'));

        // Ambil tipe transaksi aktif dari tombol yang punya warna purple (tab aktif)
        const activeTab = ['Expense', 'Income', 'Transfer', 'Debt', 'Receivable'].find(t => {
            const btn = document.getElementById('tab' + t);
            return btn && btn.classList.contains('text-[#FCA5FF]');
        });

        // KEMBALIKAN DISPLAY KE DEFAULT BERDASARKAN TAB AKTIF
        sDiv.style.display = (activeTab === 'Income') ? 'none' : 'block';
        dDiv.style.display = (activeTab === 'Expense') ? 'none' : 'block';

        // LOGIKA KHUSUS HUTANG & PIUTANG (OVERRIDE DEFAULT)
        if (categoryName === 'Dapat Hutangan') {
            sDiv.style.display = 'none'; 
            sIn.value = syH ? syH.id : '';
            dDiv.style.display = 'block'; // Pastikan Destinasi Muncul buat manual pick
            dIn.value = ''; dLb.innerText = '-- Terima Ke --';
            window.availableDestWallets = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
        } 
        else if (categoryName === 'Bayar Cicilan Hutang') {
            dDiv.style.display = 'none'; 
            dIn.value = syH ? syH.id : '';
            sDiv.style.display = 'block'; // Pastikan Source Muncul buat manual pick
            sIn.value = ''; sLb.innerText = '-- Pakai Dompet --';
            window.availableSourceWallets = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
        }
        else if (categoryName === 'Ngasih Piutang') {
            dDiv.style.display = 'none'; 
            dIn.value = syP ? syP.id : '';
            sDiv.style.display = 'block';
            sIn.value = ''; sLb.innerText = '-- Pakai Dompet --';
            window.availableSourceWallets = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
        }
        else if (categoryName === 'Terima Bayar Piutang') {
            sDiv.style.display = 'none'; 
            sIn.value = syP ? syP.id : '';
            dDiv.style.display = 'block';
            dIn.value = ''; dLb.innerText = '-- Terima Ke --';
            window.availableDestWallets = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
        }
    }

    // 4. LOGIKA TIPE TRANSAKSI (TABS)
    function setType(type) {
        const colors = {
            'Expense': 'text-red-500',
            'Income': 'text-green-500',
            'Transfer': 'text-purple-500',
            'Debt': 'text-orange-500',
            'Receivable': 'text-yellow-500'
        };

        ['Expense', 'Income', 'Transfer', 'Debt', 'Receivable'].forEach(t => {
            const btn = document.getElementById('tab' + t);
            if (btn) {
                btn.className = (t === type) 
                    ? `w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-800 to-gray-700 border border-white/20 shadow-md transition-all ${colors[t]}`
                    : "w-full text-xs font-bold uppercase tracking-widest py-3 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/5 text-gray-500 hover:text-white transition-all";
            }
        });

        window.activeCategories = window.allCategories.filter(cat => cat.type.name.toLowerCase() === type.toLowerCase());
        
        // Auto-select Kategori jika cuma 1 (Transfer)
        if (window.activeCategories.length === 1) {
            selectCategory(window.activeCategories[0].id, window.activeCategories[0].category_name, window.activeCategories[0].icon);
        } else {
            document.getElementById('catLabel').innerText = '-- Pilih Kategori --';
            document.getElementById('category_id').value = '';
        }

        const sourceDiv = document.getElementById('sourceDiv');
        const destDiv = document.getElementById('destDiv');
        const subjectDiv = document.getElementById('subjectDiv');
        
        sourceDiv.style.display = 'block';
        destDiv.style.display = 'block';
        subjectDiv.style.display = (['Debt', 'Receivable'].includes(type)) ? 'block' : 'none';

        if (type === 'Expense') {
            destDiv.style.display = 'none';
            window.availableSourceWallets = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
            const merchant = window.systemWallets.find(w => w.name.toLowerCase().includes('merchant'));
            document.getElementById('destination_wallet_id').value = merchant ? merchant.id : '';
        } 
        else if (type === 'Income') {
            sourceDiv.style.display = 'none';
            window.availableDestWallets = window.allWallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
            const external = window.systemWallets.find(w => w.name.toLowerCase().includes('external'));
            document.getElementById('source_wallet_id').value = external ? external.id : '';
        }
        else if (type === 'Transfer') {
            window.availableSourceWallets = window.allWallets.filter(w => !['System', 'Debt', 'Receivable'].includes(w.group_type));
            window.availableDestWallets = [...window.availableSourceWallets];
        }
    }

    // 5. RENDER LISTS
    function renderCategories() {
        const list = document.getElementById('categoryList');
        list.innerHTML = '';
        window.activeCategories.forEach(cat => {
            const icon = cat.icon.includes('/') ? `<img src="/storage/${cat.icon}" class="w-full h-full object-cover rounded-xl">` : cat.icon;
            list.innerHTML += `
                <div onclick="selectCategory('${cat.id}', '${cat.category_name}', '${cat.icon}')" class="bg-[#1A1A1A] border border-[#262626] p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all">
                    <div class="w-12 h-12 bg-[#262626] rounded-xl flex items-center justify-center text-xl border border-[#333] overflow-hidden">${icon}</div>
                    <span class="text-sm font-bold text-white">${cat.category_name}</span>
                </div>`;
        });
    }

    function renderWallets(mode) {
        window.currentWalletModalMode = mode;
        const list = document.getElementById('walletList');
        list.innerHTML = '';
        
        let wallets = mode === 'source' ? window.availableSourceWallets : window.availableDestWallets;
        
        const otherValue = document.getElementById(mode === 'source' ? 'destination_wallet_id' : 'source_wallet_id').value;
        if (otherValue) wallets = wallets.filter(w => w.id != otherValue);

        wallets.forEach(w => {
            const icon = w.icon.includes('/') ? `<img src="/storage/${w.icon}" class="w-full h-full object-cover rounded-xl">` : w.icon;
            
            // Tampilkan saldo kalau dia dompet Aset/Liquid (Bukan System)
            const isAsset = ['Asset', 'Liquid'].includes(w.group_type);
            const balanceInfo = isAsset 
                ? `<p class="text-[10px] text-gray-500 font-bold tracking-widest mt-0.5 uppercase">Saldo: <span class="text-[#FCA5FF]">Rp ${new Intl.NumberFormat('id-ID').format(w.balance)}</span></p>` 
                : '';

            list.innerHTML += `
                <div onclick="selectWallet('${w.id}', '${w.name}', '${w.icon}')" class="bg-[#1A1A1A] border border-[#262626] p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all group hover:border-[#FCA5FF]/30">
                    <div class="w-12 h-12 bg-[#262626] rounded-xl flex items-center justify-center text-xl border border-[#333] overflow-hidden shadow-inner group-hover:scale-105 transition-transform">${icon}</div>
                    <div class="flex-1">
                        <span class="text-sm font-bold text-white block">${w.name}</span>
                        ${balanceInfo}
                    </div>
                </div>`;
        });
    }

    // 6. INIT & FORMATTING
    window.initCreateTransaction = function() {
        window.allCategories = @json($categories);
        window.allWallets = @json($wallets);
        window.systemWallets = @json($systemWallets);

        const displayAmount = document.getElementById('display_amount');
        if (displayAmount) {
            displayAmount.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, '');
                document.getElementById('raw_amount').value = val;
                this.value = val ? new Intl.NumberFormat('id-ID').format(parseInt(val)) : '';
            });
        }
        setType('Expense');
    };

    document.addEventListener('livewire:navigated', window.initCreateTransaction);
    document.addEventListener('DOMContentLoaded', window.initCreateTransaction);

    function validateForm() {
        if (new Date(document.getElementById('dateInput').value) > new Date()) {
            document.getElementById('dateError').classList.remove('hidden'); return false;
        }
        return true;
    }
</script>
</x-app-layout>