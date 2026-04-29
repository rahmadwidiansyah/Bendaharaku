<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto">
        
        {{-- HEADER: Sapaan & Foto Profil --}}
        <header class="flex justify-between items-center mb-6 pt-4 animate-fade-in-up">
            <div>
                @php
                    $hour = \Carbon\Carbon::now('Asia/Jakarta')->format('H');
                    if ($hour < 12) { $greeting = 'Selamat Pagi'; $emoji = '☀️'; } 
                    elseif ($hour < 15) { $greeting = 'Selamat Siang'; $emoji = '🌤️'; } 
                    elseif ($hour < 18) { $greeting = 'Selamat Sore'; $emoji = '🌇'; } 
                    else { $greeting = 'Selamat Malam'; $emoji = '🌙'; }
                @endphp
                <p class="text-[10px] text-[#FCA5FF] font-black uppercase tracking-[0.3em] mb-0.5 opacity-80">✨ Hello</p>
                <h1 class="text-2xl font-black text-white tracking-tight leading-none">{{ Auth::user()->name }}</h1>
                <div class="flex items-center gap-2 mb-1">
                    <p class="text-[12px] text-gray-400 font-bold uppercase tracking-[0.1em]">{{ $greeting }}</p>
                    <span class="text-sm">{{ $emoji }}</span>
                </div>
            </div>

            @php
                $user = Auth::user();
                $avatar = $user->avatar;
                $isUrl = $avatar && Str::startsWith($avatar, ['http://', 'https://']);
                $hasAvatar = !empty($avatar) && (Str::contains($avatar, ['.png', '.jpg', '.jpeg', '.webp', '/']) || $isUrl);
                $avatarSrc = $hasAvatar ? ($isUrl ? $avatar : asset('storage/'.$avatar)) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=1A1A1A&color=FCA5FF&bold=true';
            @endphp
            
            <a href="{{ route('profile.edit') }}" wire:navigate class="relative block w-12 h-12 rounded-full border-2 border-[#FCA5FF] p-0.5 bg-[#1A1A1A] active:scale-90 transition-transform shadow-[0_0_15px_rgba(252,165,255,0.3)]">
                <img src="{{ $avatarSrc }}" alt="{{ $user->name }}" class="w-full h-full rounded-full object-cover">
            </a>
        </header>

 
        @php
            $insightType = 'info'; // default
            $insightMsg = 'Selamat datang di Bendaharaku! Yuk catat keuanganmu hari ini.';
            $insightIcon = '💡';

            if ($thisMonthExpense > 0 && $thisMonthIncome > 0) {
                $expenseRatio = ($thisMonthExpense / $thisMonthIncome) * 100;
                if ($expenseRatio >= 80) {
                    $insightType = 'danger';
                    $insightMsg = 'Awas! Pengeluaran bulan ini sudah mendekati total pemasukanmu.';
                    $insightIcon = '⚠️';
                } elseif ($expenseRatio <= 40) {
                    $insightType = 'success';
                    $insightMsg = 'Bagus sekali! Pengeluaranmu bulan ini sangat terjaga.';
                    $insightIcon = '✨';
                } else {
                    $insightMsg = 'Arus kas bulan ini berjalan normal. Terus catat pengeluaranmu!';
                    $insightIcon = '📊';
                }
            } elseif ($thisMonthExpense > 0 && $thisMonthIncome == 0) {
                $insightType = 'warning';
                $insightMsg = 'Belum ada pemasukan bulan ini, tapi pengeluaran terus jalan. Hati-hati!';
                $insightIcon = '🚨';
            }
        @endphp

        <div id="insight-box" class="mb-6 p-3 rounded-xl border items-center justify-between gap-3 animate-fade-in-up delay-100 text-[10px] uppercase font-bold tracking-widest shadow-sm
            {{ $insightType == 'danger' ? 'bg-red-950/40 border-red-900/50 text-red-400 flex' : 
              ($insightType == 'success' ? 'bg-green-950/40 border-green-900/50 text-green-400 flex' : 
              ($insightType == 'warning' ? 'bg-yellow-950/40 border-yellow-900/50 text-yellow-400 flex' : 
              'bg-gray-900/80 border-gray-900/50 text-gray-400 flex')) }}" style="display: none;">
            <div class="flex items-center gap-3">
                <span class="text-base drop-shadow-md">{{ $insightIcon }}</span>
                <p class="leading-relaxed">{{ $insightMsg }}</p>
            </div>
            <button onclick="dismissInsight()" class="text-current opacity-70 hover:opacity-100 transition-opacity p-1 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <script>
            document.addEventListener('livewire:navigated', checkInsightVisibility);
            document.addEventListener('DOMContentLoaded', checkInsightVisibility);

            function checkInsightVisibility() {
                if (!sessionStorage.getItem('insightDismissed')) {
                    const box = document.getElementById('insight-box');
                    if (box) box.style.display = 'flex';
                }
            }

            function dismissInsight() {
                sessionStorage.setItem('insightDismissed', 'true');
                const box = document.getElementById('insight-box');
                if (box) box.style.display = 'none';
            }
            
            // Panggil sekali untuk inisialisasi awal jika DOMContentLoaded sudah lewat
            checkInsightVisibility();
        </script>

        {{-- HERO CARD: Total Portofolio --}}
        <div class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/5 overflow-hidden shadow-2xl mb-5 group animate-fade-in-up delay-200">
            <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="absolute inset-x-0 bottom-0 opacity-20 pointer-events-none h-24">
                <svg viewBox="0 0 400 150" preserveAspectRatio="none" class="w-full h-full">
                    <defs>
                        <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#FCA5FF; stop-opacity:0.4" />
                            <stop offset="100%" style="stop-color:#FCA5FF; stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path class="main-graph-fill" fill="url(#chartGradient)"></path>
                    <path class="main-graph-line" d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50" stroke="#FCA5FF" stroke-width="3" fill="none" stroke-linecap="round"></path>
                </svg>
            </div>

            

            <div class="relative z-10 p-7 pb-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2">
                        <div id="status-dot" class="w-1.5 h-1.5 rounded-xl bg-gray-600 transition-all duration-700"></div>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-[0.2em]">Total Kekayaan</p>
                    </div>
                    <span class="text-xs font-bold text-green-400 bg-green-400/10 px-2 py-0.5 rounded-full border border-green-400/20">Live</span>
                </div>
                
                <div class="flex items-baseline gap-1.5 mb-4">
                    <span class="text-lg font-medium text-gray-500">Rp</span>
                    <h2 class="text-3xl font-black text-white tracking-tight">
                        {{ number_format($totalPortfolio, 0, ',', '.') }}
                    </h2>
                </div>

                {{-- BREAKDOWN: Liquid & Investasi --}}
                <div class="flex items-center gap-4 pt-3 border-t border-white/10 mt-1">
                    <div class="flex-1">
                        <div class="flex items-center gap-1.5 mb-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400 shadow-[0_0_5px_rgba(96,165,250,0.5)]"></div>
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Liquid</p>
                        </div>
                        <p class="text-sm font-bold text-white tracking-tight">
                            <span class="text-[10px] text-gray-500 mr-0.5">Rp</span>{{ number_format($totalLiquid ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/10 to-transparent"></div>
                    <div class="flex-1">
                        <div class="flex items-center gap-1.5 mb-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-purple-400 shadow-[0_0_5px_rgba(192,132,252,0.5)]"></div>
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Investasi</p>
                        </div>
                        <p class="text-sm font-bold text-white tracking-tight">
                            <span class="text-[10px] text-gray-500 mr-0.5">Rp</span>{{ number_format($totalInvest ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        

        {{-- MINI CASHFLOW (Bulan Ini) --}}
        <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-200">
            <div class="bg-gradient-to-br from-green-950/20 to-gray-800 border border-green-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Pemasukan</p>
                    <p class="text-sm font-bold text-white tracking-tight mt-0.5"><span class="text-xs text-gray-500 mr-1">Rp</span><span class="text-green-400">{{ number_format($thisMonthIncome, 0, ',', '.') }}</span></p>
                </div>
            </div>
            <div class="bg-gradient-to-br from-red-950/20 to-gray-800 border border-red-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Pengeluaran</p>
                    <p class="text-sm font-bold text-white tracking-tight mt-0.5"><span class="text-xs text-gray-500 mr-1">Rp</span><span class="text-red-400">{{ number_format($thisMonthExpense, 0, ',', '.') }}</span></p>
                </div>
            </div>
        </div>

        {{-- WALLET SECTION --}}
        <div class="flex justify-between items-center mb-5 px-1 gap-3 animate-fade-in-up delay-300">
            <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> Aset Saya
            </h2>
            <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
            <a href="{{ route('wallets.create') }}" wire:navigate class="text-gray-400 hover:text-white transition-colors active:scale-90">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-300">
            @forelse($wallets as $wallet)
                @php
                    $rawIcon = $wallet->icon ?? '💳';
                    $isImage = \Illuminate\Support\Str::contains($rawIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
                @endphp
                <a href="{{ route('wallets.show', $wallet) }}" wire:navigate class="relative group bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-4 border border-white/5 active:scale-95 transition-all shadow-xl overflow-hidden h-[100px] flex flex-col justify-between hover:border-blue-500/30">
                    <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    <div class="absolute inset-x-0 bottom-0 opacity-[0.15] pointer-events-none h-14">
                        <svg viewBox="0 0 200 100" preserveAspectRatio="none" class="w-full h-full">
                            <path class="wallet-graph-fill text-blue-400" fill="currentColor"></path>
                            <path class="wallet-graph-line text-blue-400" stroke="currentColor" stroke-width="2.5" fill="none"></path>
                        </svg>
                    </div>
                    <div class="relative z-10 flex items-center gap-2">
                        <div class="w-8 h-8 shrink-0 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center text-base border border-white/10 shadow-inner group-hover:scale-110 transition-transform overflow-hidden">
                            @if($isImage) <img src="{{ asset('storage/' . $rawIcon) }}" class="w-full h-full object-cover"> @else {{ $rawIcon }} @endif
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tight truncate leading-tight group-hover:text-white transition-colors">{{ $wallet->name }}</p>
                    </div>
                    <div class="relative z-10">
                        <p class="text-lg font-bold text-white tracking-tighter truncate"><span class="text-[10px] text-gray-600 font-medium mr-0.5">Rp</span>{{ number_format($wallet->balance, 0, ',', '.') }}</p>
                    </div>
                </a>
            @empty
                <div class="col-span-2 text-center py-6 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/5 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    <span class="text-2xl mb-2 block relative z-10">🏦</span>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-widest relative z-10">Belum Ada Dompet Aktif</p>
                </div>
            @endforelse
        </div>

        {{-- KEWAJIBAN SECTION --}}
        <div class="flex justify-between items-center mb-4 px-1 gap-3 animate-fade-in-up delay-400">
            <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span> Kewajiban
            </h2>
            <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
            <a href="{{ route('loans.index', ['type' => 'hutang']) }}" wire:navigate class="text-xs font-bold text-purple-400 hover:text-white transition-colors uppercase tracking-widest">
                Semua
            </a>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-400">
            <a href="{{ route('loans.index', ['type' => 'hutang']) }}" wire:navigate class="active:scale-95 transition-transform group">
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/5 relative overflow-hidden h-[100px] hover:border-yellow-400">
                    <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    <div class="absolute inset-x-0 bottom-0 opacity-10 pointer-events-none h-12">
                        <svg viewBox="0 0 200 100" preserveAspectRatio="none" class="w-full h-full">
                            <path class="loan-graph-fill text-purple-400" fill="currentColor"></path>
                            <path class="loan-graph-line text-purple-400" stroke="currentColor" stroke-width="3" fill="none"></path>
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="loan-dot w-1.5 h-1.5 rounded-full bg-gray-600 transition-all duration-700" data-color="#E5D07E"></div>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Hutang</h3>
                        </div>
                        <p class="text-base font-bold text-white tracking-tight truncate"><span class="text-xs text-gray-600 mr-1">Rp</span>{{ number_format($totalHutang, 0, ',', '.') }}</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('loans.index', ['type' => 'piutang']) }}" wire:navigate class="active:scale-95 transition-transform group">
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/5 relative overflow-hidden h-[100px] hover:border-purple-400">
                    <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    <div class="absolute inset-x-0 bottom-0 opacity-10 pointer-events-none h-12">
                        <svg viewBox="0 0 200 100" preserveAspectRatio="none" class="w-full h-full">
                            <path class="loan-graph-fill text-purple-400" fill="currentColor"></path>
                            <path class="loan-graph-line text-purple-400" stroke="currentColor" stroke-width="3" fill="none"></path>
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="loan-dot w-1.5 h-1.5 rounded-full bg-gray-600 transition-all duration-700" data-color="#FCA5FF"></div>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Piutang</h3>
                        </div>
                        <p class="text-base font-bold text-white tracking-tight truncate"><span class="text-xs text-gray-600 mr-1">Rp</span>{{ number_format($totalPiutang, 0, ',', '.') }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- RECENT ACTIVITY (AKTIVITAS TERAKHIR) --}}
        <div class="flex justify-between items-center mb-4 px-1 gap-3 animate-fade-in-up delay-500">
            <h2 class="text-[11px] font-bold text-white uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Aktivitas Terakhir
            </h2>
            <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
            <a href="{{ route('transactions.index') }}" wire:navigate class="text-xs font-bold text-purple-400 hover:text-white transition-colors uppercase tracking-widest">
                Semua
            </a>
            
        </div>

<div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/5 rounded-xl p-2 mb-8 animate-fade-in-up delay-500 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
    @forelse($recentTransactions as $trx)
        @php
            $isIncome = $trx->type->name === 'Income';
            $isExpense = $trx->type->name === 'Expense';
            
            // Format Nominal
            $amountPrefix = $isIncome ? '+' : ($isExpense ? '-' : '');
            $amountColor = $isIncome ? 'text-green-400' : ($isExpense ? 'text-white' : 'text-blue-400');
            
            // Logika Icon & Data
            $rawIcon = $trx->category->icon ?? '📝';
            $isImg = \Illuminate\Support\Str::contains($rawIcon, ['.png', '.jpg', '.jpeg', '.webp', '/']);
        @endphp

        {{-- GANTI DARI <a> KE <button> BIAR BISA BUKA MODAL --}}
        <button type="button" 
            onclick="openDetailModal(this)"
            data-id="{{ $trx->id }}" 
            data-type="{{ $trx->type->name }}" 
            data-amount="{{ number_format($trx->amount, 0, ',', '.') }}" 
            data-category="{{ $trx->category->category_name ?? 'Transfer' }}" 
            data-icon="{{ $rawIcon }}" 
            data-is-image="{{ $isImg ? 'true' : 'false' }}" 
            data-icon-url="{{ asset('storage/' . $rawIcon) }}"
            data-date="{{ \Carbon\Carbon::parse($trx->date)->translatedFormat('d M Y') }}" 
            data-time="{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}"
            data-source="{{ $trx->sourceWallet->name }}" 
            data-dest="{{ $trx->destinationWallet->name }}" 
            data-subject="{{ $trx->subject }}" 
            data-notes="{{ $trx->notes }}"
            class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-[#262626]/50 active:scale-95 transition-all group text-left relative overflow-hidden">
            <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="w-10 h-10 shrink-0 bg-[#121212] border border-[#333] rounded-xl flex items-center justify-center text-lg overflow-hidden">
                    @if($isImg) <img src="{{ asset('storage/'.$rawIcon) }}" class="w-full h-full object-cover"> @else {{ $rawIcon }} @endif
                </div>
                <div class="truncate">
                    <p class="text-xs font-bold text-white truncate">{{ $trx->category->category_name ?? 'Transfer' }}</p>
                    <p class="text-[9px] text-gray-500 uppercase tracking-widest truncate mt-0.5">
                        {{ \Carbon\Carbon::parse($trx->date)->format('d M') }} • {{ Str::limit($trx->notes ?? $trx->type->name, 15) }}
                    </p>
                </div>
            </div>
            <div class="text-right shrink-0 pl-2">
                <p class="text-sm font-bold tracking-tight {{ $amountColor }}">
                    {{ $amountPrefix }}Rp{{ number_format($trx->amount, 0, ',', '.') }}
                </p>
            </div>
        </button>
    @empty
        <div class="text-center py-6">
            <span class="text-2xl mb-2 block opacity-50">💸</span>
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Belum Ada Transaksi</p>
        </div>
    @endforelse
</div>

        {{-- FAB & Nav --}}
       
        <x-create-transaction />
        <x-bottom-nav />
    </div>

    {{-- Script Animasi (Tetap Sama dengan Kode Sebelumnya) --}}
    <style>
        @keyframes fade-in-up { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
        .animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }
        .delay-500 { animation-delay: 500ms; }
    </style>

    <script>
        document.addEventListener('livewire:navigated', function() {
            if (!document.getElementById('status-dot')) return;

            function generatePath(totalPoints, width, heightRange) {
                const points = [];
                for (let i = 0; i < totalPoints; i++) {
                    points.push({ x: i * (width / (totalPoints - 1)), y: Math.floor(Math.random() * (heightRange.max - heightRange.min)) + heightRange.min });
                }
                let d = `M ${points[0].x},${points[0].y} `;
                for (let i = 0; i < points.length - 1; i++) {
                    const mx = (points[i].x + points[i+1].x) / 2;
                    const my = (points[i].y + points[i+1].y) / 2;
                    d += `Q ${points[i].x},${points[i].y} ${mx},${my} `;
                }
                d += `T ${width},${points[points.length-1].y}`;
                return d;
            }

            const mainLine = document.querySelector('.main-graph-line');
            const mainFill = document.querySelector('.main-graph-fill');
            const statusDot = document.getElementById('status-dot');

            if (mainLine) {
                const length = mainLine.getTotalLength();
                mainLine.style.strokeDasharray = length;
                mainLine.style.strokeDashoffset = length;
                mainFill.setAttribute('d', `${mainLine.getAttribute('d')} L400,150 L0,150 Z`);
                mainFill.style.opacity = '0';

                setTimeout(() => {
                    mainLine.style.transition = 'stroke-dashoffset 2s ease-in-out';
                    mainLine.style.strokeDashoffset = '0';

                    setTimeout(() => {
                        if(statusDot) {
                            statusDot.classList.replace('bg-gray-600', 'bg-[#FCA5FF]');
                            statusDot.style.boxShadow = '0 0 10px #FCA5FF';
                        }
                        if(mainFill) {
                            mainFill.style.transition = 'opacity 1s ease';
                            mainFill.style.opacity = '1';
                        }
                        animateWallets();
                        animateLoans();
                    }, 1800);
                }, 300);
            }

            function animateWallets() {
                const lines = document.querySelectorAll('.wallet-graph-line');
                const fills = document.querySelectorAll('.wallet-graph-fill');
                lines.forEach((line, index) => {
                    const d = generatePath(7, 200, {min: 20, max: 60});
                    line.setAttribute('d', d);
                    fills[index].setAttribute('d', `${d} L200,100 L0,100 Z`);
                    fills[index].style.opacity = '0';
                    const len = line.getTotalLength();
                    line.style.strokeDasharray = len;
                    line.style.strokeDashoffset = len;
                    setTimeout(() => {
                        line.style.transition = 'stroke-dashoffset 1.5s ease-out';
                        line.style.strokeDashoffset = '0';
                        setTimeout(() => { fills[index].style.transition = 'opacity 1s ease'; fills[index].style.opacity = '1'; }, 1300);
                    }, 200);
                });
            }

            function animateLoans() {
                const lines = document.querySelectorAll('.loan-graph-line');
                const fills = document.querySelectorAll('.loan-graph-fill');
                const dots = document.querySelectorAll('.loan-dot');
                lines.forEach((line, index) => {
                    const isHutang = index === 0;
                    const d = generatePath(5, 200, isHutang ? {min: 50, max: 80} : {min: 20, max: 40});
                    line.setAttribute('d', d);
                    fills[index].setAttribute('d', `${d} L200,100 L0,100 Z`);
                    fills[index].style.opacity = '0';
                    const len = line.getTotalLength();
                    line.style.strokeDasharray = len;
                    line.style.strokeDashoffset = len;
                    setTimeout(() => {
                        line.style.transition = 'stroke-dashoffset 1.5s ease-in-out';
                        line.style.strokeDashoffset = '0';
                        setTimeout(() => {
                            const color = dots[index].getAttribute('data-color');
                            dots[index].style.backgroundColor = color;
                            dots[index].style.boxShadow = `0 0 8px ${color}`;
                            fills[index].style.transition = 'opacity 1s ease';
                            fills[index].style.opacity = '1';
                        }, 1200);
                    }, 150);
                });
            }
        });
    </script>
<x-transaction-detail-modal />
</x-app-layout>