<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative">
        
        <header class="mb-4 pt-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">Histori Transaksi</h1>
        </header>

        <form id="filterForm" action="{{ route('transactions.index') }}" method="GET" class="flex gap-2 mb-6">
            <input type="hidden" name="type" id="typeInput" value="{{ request('type') }}">
            
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari catatan..." 
                    class="w-full bg-[#1A1A1A] border border-[#262626] text-white rounded-[1rem] p-3.5 pl-11 text-xs focus:ring-1 focus:ring-[#FCA5FF] shadow-inner transition-colors">
                <svg class="w-4 h-4 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            
            {{-- PAKAI COMPONENT DATE MODAL BIAR GAYA TETEP SAMA TAPI POSISI BENER --}}
            <x-date-modal action="{{ route('transactions.index') }}" :start-date="$startDate" :end-date="$endDate">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="type" value="{{ request('type') }}">
            </x-date-modal>
        </form>

        <div class="flex justify-between items-center mb-4">
            {{-- Klik teks periode ini juga bisa buka modal tanggal --}}
            <button onclick="toggleDateModal()" class="text-[10px] font-bold text-[#FCA5FF] uppercase tracking-widest active:opacity-50 transition-all">
                Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </button>
            
            <button type="button" onclick="toggleModal('sortModal')" class="flex items-center gap-1.5 bg-[#1A1A1A] border border-[#262626] px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ request('type') ? 'text-[#FCA5FF] border-[#FCA5FF]/50' : 'text-gray-500' }} active:scale-95 transition-all">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" /></svg>
                {{ request('type') ? request('type') : 'Semua Tipe' }}
            </button>
        </div>

        @php $groupedTransactions = $transactions->groupBy('date'); @endphp

        <div class="space-y-6">
            @forelse($groupedTransactions as $date => $dayTransactions)
                @php
                    $dateObj = \Carbon\Carbon::parse($date);
                    $isToday = $dateObj->isToday();
                    $dateLabel = $isToday ? 'Hari Ini' : ($dateObj->isYesterday() ? 'Kemarin' : $dateObj->translatedFormat('d M Y'));
                    
                    $dayIncome = $dayTransactions->where('type.name', 'Income')->sum('amount');
                    $dayExpense = $dayTransactions->where('type.name', 'Expense')->sum('amount');
                    
                    $hText = $isToday ? 'text-[#FCA5FF]' : 'text-purple-400';
                    $hBorder = $isToday ? 'border-[#FCA5FF]/40' : 'border-purple-400/30';
                @endphp

                <div>
                    <div class="flex justify-between items-center mb-2.5 px-1 border-b {{ $hBorder }} pb-1.5 transition-colors">
                        <h3 class="text-[10px] font-bold {{ $hText }} uppercase tracking-widest flex items-center gap-1.5"> {{ $dateLabel }} </h3>
                        <div class="text-[10px] font-bold flex gap-2.5 tracking-wide">
                            @if($dayIncome > 0) <span class="text-green-400/90">+{{ number_format($dayIncome, 0, ',', '.') }}</span> @endif
                            @if($dayExpense > 0) <span class="text-white/90">-{{ number_format($dayExpense, 0, ',', '.') }}</span> @endif
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        @foreach($dayTransactions as $trx)
                            @php
                                $typeName = match($trx->type->name) { 'Income'=>'Pemasukan', 'Expense'=>'Pengeluaran', 'Transfer'=>'Transfer', 'Debt'=>'Hutang', 'Receivable'=>'Piutang', default=>'Lainnya' };
                                $typeColor = match($trx->type->name) { 'Income'=>'text-green-400 bg-green-400/10 border-green-400/20', 'Expense'=>'text-gray-400 bg-gray-400/10 border-gray-400/20', 'Transfer'=>'text-blue-400 bg-blue-400/10 border-blue-400/20', 'Debt'=>'text-[#E5D07E] bg-[#E5D07E]/10 border-[#E5D07E]/20', 'Receivable'=>'text-[#FCA5FF] bg-[#FCA5FF]/10 border-[#FCA5FF]/20', default=>'text-gray-500' };
                                $rawIcon = $trx->category->icon ?? '📄';
                                $isImage = Str::contains($rawIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
                            @endphp
                            
                            <button type="button" onclick="openDetailModal(this)"
                                data-id="{{ $trx->id }}" data-type="{{ $trx->type->name }}" data-amount="{{ number_format($trx->amount, 0, ',', '.') }}" data-category="{{ $trx->category->category_name }}" data-icon="{{ $rawIcon }}" data-is-image="{{ $isImage ? 'true' : 'false' }}" data-icon-url="{{ $isImage ? asset('storage/' . $rawIcon) : '' }}" data-date="{{ \Carbon\Carbon::parse($trx->date)->translatedFormat('d M Y') }}" data-time="{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }} WIB" data-source="{{ $trx->sourceWallet->name }}" data-dest="{{ $trx->destinationWallet->name }}" data-subject="{{ $trx->subject }}" data-notes="{{ $trx->notes }}"
                                class="w-full text-left bg-[#1A1A1A] p-3 rounded-2xl border border-[#262626] hover:bg-[#202020] active:scale-[0.98] transition-all relative overflow-hidden shadow-sm group">
                                
                                <div class="flex items-center gap-3 relative z-10">
                                    <div class="w-10 h-10 rounded-xl bg-[#262626] flex items-center justify-center text-lg border border-[#333] shrink-0 shadow-inner overflow-hidden p-0.5">
                                        @if($isImage) <img src="{{ asset('storage/' . $rawIcon) }}" class="w-full h-full object-cover rounded-[0.6rem]"> @else {{ $rawIcon }} @endif
                                    </div>

                                    <div class="flex-1 min-w-0 pr-2">
                                        <p class="text-xs font-bold text-white leading-tight mb-1.5">{{ $trx->category->category_name }}</p>
                                        <div class="flex items-center gap-2 opacity-80">
                                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-tight whitespace-nowrap">{{ $trx->sourceWallet->name }}</span>
                                            <svg class="w-2.5 h-2.5 text-[#FCA5FF] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                            <span class="text-[9px] text-white font-bold uppercase tracking-tight whitespace-nowrap">{{ $trx->destinationWallet->name }}</span>
                                        </div>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <p class="text-xs font-black {{ $trx->type->name === 'Income' ? 'text-green-400' : 'text-white' }}">
                                            {{ $trx->type->name === 'Income' ? '+' : '-' }}{{ number_format($trx->amount, 0, ',', '.') }}
                                        </p>
                                        <div class="flex items-center justify-end gap-1.5 mt-1">
                                            <span class="text-[9px] text-gray-600 font-medium italic"> {{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }} </span>
                                            <span class="text-[7px] uppercase tracking-widest font-black px-1 py-0.5 rounded border {{ $typeColor }}"> {{ $typeName }} </span>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-12 bg-[#1A1A1A] rounded-[2rem] border-2 border-dashed border-[#262626]">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Data Kosong</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $transactions->links() }}</div>
    </div>

    {{-- MODAL SORT TIPE (Ciri Khas Abang) --}}
    <div id="sortModal" class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4" onclick="if(event.target === this) toggleModal('sortModal')">
        <div class="w-full max-w-sm bg-[#1A1A1A] rounded-[2rem] border border-[#262626] p-6 animate-pop-in relative">
            <h3 class="text-sm font-bold text-white mb-6 text-center uppercase tracking-widest">Filter Tipe</h3>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="setSortType('')" class="col-span-2 py-3 rounded-xl border {{ !request('type') ? 'bg-[#FCA5FF] text-black border-transparent' : 'bg-[#262626] text-white border-[#333]' }} text-[10px] font-bold uppercase tracking-widest">Semua Tipe</button>
                @foreach(['Income' => 'Pemasukan', 'Expense' => 'Pengeluaran', 'Transfer' => 'Transfer', 'Debt' => 'Hutang', 'Receivable' => 'Piutang'] as $key => $label)
                    <button onclick="setSortType('{{ $key }}')" class="py-3 rounded-xl border {{ request('type') == $key ? 'bg-[#FCA5FF] text-black border-transparent' : 'bg-[#262626] text-white border-[#333]' }} text-[10px] font-bold uppercase tracking-widest">{{ $label }}</button>
                @endforeach
            </div>
            <button type="button" onclick="toggleModal('sortModal')" class="w-full mt-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Batal</button>
        </div>
    </div>
<x-create-transaction />
    <script>
        function toggleModal(id) { document.getElementById(id).classList.toggle('hidden'); }
        function setSortType(type) {
            document.getElementById('typeInput').value = type;
            document.getElementById('filterForm').submit();
        }
    </script>

    <style>
        @keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    </style>

    <x-bottom-nav />
    <x-transaction-detail-modal />
</x-app-layout>