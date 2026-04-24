<x-app-layout>
    {{-- Glow Background Ambient --}}
    <div class="fixed top-[-5%] left-[50%] -translate-x-1/2 w-[100%] max-w-md h-[400px] bg-gradient-to-b from-[#FCA5FF]/5 to-transparent pointer-events-none z-0"></div>

    <div class="p-5 pb-40 max-w-md mx-auto relative z-10 overflow-x-hidden">
        
        {{-- HEADER --}}
        <header class="flex justify-between items-end mb-6 pt-4 animate-fade-in-up">
            <div>
                <p class="text-[10px] text-[#FCA5FF] font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#FCA5FF] shadow-[0_0_5px_#FCA5FF]"></span>
                    Laporan
                </p>
                <h1 class="text-3xl font-black text-white tracking-tight leading-none">Analitik</h1>
            </div>
            
            {{-- KOMPONEN KALENDER --}}
            <x-date-modal :action="route('analytics.index')" :start-date="$startDate" :end-date="$endDate" />
        </header>

        {{-- RINGKASAN --}}
        <div class="grid grid-cols-2 gap-3 mb-6 animate-fade-in-up delay-100">
            <div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] p-5 rounded-[1.8rem] border border-white/5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 bg-green-500/10 rounded-bl-full blur-xl group-hover:bg-green-500/20 transition-colors"></div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-400 shadow-[0_0_5px_rgba(74,222,128,0.5)]"></div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Pemasukan</p>
                </div>
                <p class="text-lg font-bold text-green-400 tracking-tight truncate relative z-10">
                    <span class="text-[10px] mr-0.5 opacity-70">+Rp</span>{{ number_format($totalIncome, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] p-5 rounded-[1.8rem] border border-white/5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 bg-gray-500/10 rounded-bl-full blur-xl group-hover:bg-gray-500/20 transition-colors"></div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-gray-400 shadow-[0_0_5px_rgba(156,163,175,0.5)]"></div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Pengeluaran</p>
                </div>
                <p class="text-lg font-bold text-white tracking-tight truncate relative z-10">
                    <span class="text-[10px] mr-0.5 opacity-70">-Rp</span>{{ number_format($totalExpense, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- CHART CUMULATIVE --}}
        <div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] border border-[#FCA5FF]/20 p-6 rounded-[2rem] mb-8 shadow-[0_10px_30px_rgba(252,165,255,0.05)] animate-fade-in-up delay-200 relative overflow-hidden group">
            <div class="absolute inset-0 bg-[#FCA5FF]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="flex justify-between items-start mb-6 relative z-10">
                <div>
                    <p class="text-[9px] font-bold text-[#FCA5FF] uppercase tracking-[0.2em] mb-1">Saldo Kumulatif</p>
                    <p class="text-[10px] text-gray-500 font-medium">Pergerakan total kekayaan</p>
                </div>
                <p class="text-xl font-black text-white tracking-tight bg-[#121212] px-3 py-1.5 rounded-xl border border-white/5 shadow-inner">
                    <span class="text-xs text-gray-500 mr-1">Rp</span>{{ number_format($cumulativeBalance, 0, ',', '.') }}
                </p>
            </div>
            <div class="w-full h-[140px] relative z-10">
                <canvas id="cumulativeChart"></canvas>
            </div>
        </div>

        {{-- ARUS KAS HARIAN --}}
     <div class="flex items-center gap-2 mb-4 px-1 animate-fade-in-up delay-300">
    <h2 class="text-[11px] font-bold text-white uppercase tracking-widest">Arus Kas Harian</h2>
    <div class="flex-1 h-px bg-gradient-to-r from-white/10 to-transparent"></div>
</div>

<div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] rounded-[2rem] border border-white/5 mb-8 p-5 relative overflow-hidden shadow-xl animate-fade-in-up delay-300">
    {{-- Hapus scroll-smooth di sini --}}
    <div id="chartScrollBox" class="overflow-x-auto no-scrollbar pb-1" style="scroll-behavior: auto !important;">
        <div id="chartInnerContent" style="min-width: {{ count($dailyLabels) * 45 }}px; height: 180px;">
            <canvas id="barChart"></canvas>
        </div>
    </div>
    {{-- Shadow overlay kanan tetap ada --}}
    <div class="absolute top-0 right-0 w-8 h-full bg-gradient-to-l from-[#1A1A1A] to-transparent pointer-events-none"></div>
</div>
        {{-- TAB KATEGORI --}}
        <div class="flex items-center gap-2 mb-4 px-1 animate-fade-in-up delay-400">
            <h2 class="text-[11px] font-bold text-white uppercase tracking-widest">Rincian Kategori</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-white/10 to-transparent"></div>
        </div>
        <div class="flex bg-[#121212] border border-white/5 rounded-[1.2rem] p-1.5 mb-5 shadow-inner animate-fade-in-up delay-400 relative">
            <div id="tabIndicator" class="absolute top-1.5 bottom-1.5 left-1.5 w-[calc(50%-0.375rem)] bg-[#262626] border border-white/5 shadow-md rounded-xl transition-all duration-300 ease-out z-0"></div>
            <button id="btnExpense" onclick="switchChart('expense')" class="relative z-10 flex-1 text-[10px] font-bold uppercase tracking-widest py-3 text-white transition-colors duration-300">Pengeluaran</button>
            <button id="btnIncome" onclick="switchChart('income')" class="relative z-10 flex-1 text-[10px] font-bold uppercase tracking-widest py-3 text-gray-500 transition-colors duration-300">Pemasukan</button>
        </div>

        {{-- DOUGHNUT CHART --}}
        <div class="bg-gradient-to-br from-[#1E1E1E] to-[#121212] p-6 rounded-[2.5rem] border border-white/5 mb-8 relative shadow-xl animate-fade-in-up delay-500">
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center hidden bg-[#1A1A1A]/80 backdrop-blur-sm rounded-[2.5rem] z-20">
                <span class="w-12 h-12 bg-[#262626] rounded-2xl flex items-center justify-center text-xl mb-3 border border-white/5">📭</span>
                <p class="text-[10px] font-bold text-white uppercase tracking-widest">Tidak Ada Data</p>
            </div>
            <div id="chartContainer" class="relative w-full h-56 mb-6">
                <canvas id="mainChart" class="relative z-10 w-full h-full"></canvas>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center pointer-events-none z-0">
                    <div class="w-[110px] h-[110px] rounded-full bg-[#121212] border border-white/5 shadow-inner flex flex-col items-center justify-center text-center px-2">
                        <span id="chartCenterLabel" class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mb-0.5">Total</span>
                        <span id="chartCenterValue" class="text-base font-black text-white tracking-tight line-clamp-1 w-full truncate">Rp 0</span>
                    </div>
                </div>
            </div>
            <div id="chartLegend" class="space-y-4"></div>
        </div>

    </div>

<script>
    window.myCharts = window.myCharts || {};
    
    const expenseData = { labels: @json($expensesByCategory->pluck('name')), values: @json($expensesByCategory->pluck('total')), ids: @json($expensesByCategory->pluck('id')), icons: @json($expensesByCategory->pluck('icon')), total: {{ $totalExpense }}, labelName: 'Total Pengeluaran' };
    const incomeData = { labels: @json($incomesByCategory->pluck('name')), values: @json($incomesByCategory->pluck('total')), ids: @json($incomesByCategory->pluck('id')), icons: @json($incomesByCategory->pluck('icon')), total: {{ $totalIncome }}, labelName: 'Total Pemasukan' };

    function destroyChart(id) {
        if (window.myCharts[id]) { window.myCharts[id].destroy(); delete window.myCharts[id]; }
    }

    function renderMainChart(dataObj, type) {
        const canvas = document.getElementById('mainChart');
        const emptyState = document.getElementById('emptyState');
        const chartContainer = document.getElementById('chartContainer');
        const legendContainer = document.getElementById('chartLegend');
        if (!canvas || !dataObj) return;

        destroyChart('main');
        if (!dataObj.labels || dataObj.labels.length === 0) {
            emptyState.classList.remove('hidden'); chartContainer.classList.add('opacity-0'); legendContainer.innerHTML = ''; return;
        }

        emptyState.classList.add('hidden'); chartContainer.classList.remove('opacity-0');
        const colors = type === 'expense' ? ['#FCA5FF', '#A78BFA', '#818CF8', '#60A5FA', '#38BDF8', '#4ADE80'] : ['#34D399', '#6EE7B7', '#A7F3D0', '#10B981'];

        document.getElementById('chartCenterLabel').innerText = dataObj.labelName;
        document.getElementById('chartCenterValue').innerText = 'Rp ' + (dataObj.total || 0).toLocaleString('id-ID');

        window.myCharts['main'] = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: { labels: dataObj.labels, datasets: [{ data: dataObj.values, backgroundColor: colors, borderWidth: 2, borderColor: '#121212' }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '80%', plugins: { legend: { display: false } } }
        });

        let html = '';
        dataObj.labels.forEach((label, i) => {
            let val = dataObj.values[i];
            let percentage = dataObj.total > 0 ? ((val / dataObj.total) * 100).toFixed(1) : 0;
            let bgColor = colors[i % colors.length];
            let rawIcon = dataObj.icons[i] || '📁';
            html += `<a href="/categories/${dataObj.ids[i]}" class="relative flex items-center justify-between bg-[#1A1A1A] p-3 rounded-2xl border border-white/5 overflow-hidden group hover:border-[#FCA5FF]/30 transition-all duration-300">
                        <div class="absolute top-0 bottom-0 left-0 bg-gradient-to-r from-[${bgColor}]/20 to-transparent w-[${percentage}%] opacity-30"></div>
                        <div class="flex items-center gap-3 relative z-10 w-full">
                            <div class="w-1.5 h-6 rounded-full" style="background-color: ${bgColor};"></div>
                            <div class="w-8 h-8 rounded-lg bg-[#2A2A2A] flex items-center justify-center border border-white/5 overflow-hidden p-0.5">
                                ${rawIcon.includes('/') ? `<img src="/storage/${rawIcon}" class="w-full h-full object-cover">` : `<span class="text-sm">${rawIcon}</span>`}
                            </div>
                            <div class="flex-1 min-w-0 pr-2"><p class="text-[11px] font-bold text-gray-200 truncate">${label}</p><p class="text-[9px] text-gray-500 font-bold">${percentage}%</p></div>
                            <div class="text-right shrink-0"><span class="text-xs font-black text-white block">Rp ${val.toLocaleString('id-ID')}</span></div>
                        </div>
                    </a>`;
        });
        legendContainer.innerHTML = html;
    }

    function switchChart(type) {
        const btnEx = document.getElementById('btnExpense'); const btnIn = document.getElementById('btnIncome');
        const tabIndicator = document.getElementById('tabIndicator');
        if (type === 'expense') {
            tabIndicator.style.transform = 'translateX(0)'; 
            btnEx.className = "relative z-10 flex-1 text-[10px] font-bold uppercase tracking-widest py-3 text-white transition-all";
            btnIn.className = "relative z-10 flex-1 text-[10px] font-bold uppercase tracking-widest py-3 text-gray-500 transition-all";
            renderMainChart(expenseData, 'expense');
        } else {
            tabIndicator.style.transform = 'translateX(100%)'; 
            btnIn.className = "relative z-10 flex-1 text-[10px] font-bold uppercase tracking-widest py-3 text-white transition-all";
            btnEx.className = "relative z-10 flex-1 text-[10px] font-bold uppercase tracking-widest py-3 text-gray-500 transition-all";
            renderMainChart(incomeData, 'income');
        }
    }

    function initCharts() {
        destroyChart('cumulative'); destroyChart('bar'); destroyChart('main');
        
        // Render Line Chart
        const ctxCum = document.getElementById('cumulativeChart')?.getContext('2d');
        if (ctxCum) {
            let grad = ctxCum.createLinearGradient(0, 0, 0, 140);
            grad.addColorStop(0, 'rgba(252,165,255,0.4)'); grad.addColorStop(1, 'rgba(252,165,255,0)');
            window.myCharts['cumulative'] = new Chart(ctxCum, {
                type: 'line',
                data: { labels: @json($dailyLabels), datasets: [{ data: @json($cumulativeData), borderColor: '#FCA5FF', borderWidth: 2.5, backgroundColor: grad, fill: true, tension: 0.4, pointRadius: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } } }
            });
        }

        // Render Bar Chart
        const ctxBar = document.getElementById('barChart')?.getContext('2d');
        if (ctxBar) {
            window.myCharts['bar'] = new Chart(ctxBar, {
                type: 'bar',
                data: { labels: @json($dailyLabels), datasets: [{ label: 'In', data: @json($dailyIncome), backgroundColor: '#34D399', borderRadius: 4 }, { label: 'Out', data: @json($dailyExpense), backgroundColor: '#4B5563', borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 9 } } }, y: { display: false } } }
            });
        }

        switchChart('expense');

        // LOGIKA REM PAKSA (INSTAN)
        const scrollBox = document.getElementById('chartScrollBox');
        if (scrollBox) {
            // Matikan smooth scroll lewat JS biar gak bablas
            scrollBox.style.scrollBehavior = 'auto'; 
            
            const forceScroll = () => {
                scrollBox.scrollLeft = scrollBox.scrollWidth;
            };

            // Hajar 3 kali di waktu berbeda biar gak ada alasan canvas telat render
            forceScroll(); 
            setTimeout(forceScroll, 50);
            setTimeout(forceScroll, 300);
        }
    }

    document.addEventListener('livewire:navigated', initCharts);
    document.addEventListener('DOMContentLoaded', initCharts);
</script>
    <x-create-transaction />
    <x-bottom-nav />
</x-app-layout>