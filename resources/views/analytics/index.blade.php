<x-app-layout>
    {{-- Glow Background Ambient --}}
    <div class="fixed top-[-5%] left-[50%] -translate-x-1/2 w-[100%] max-w-md h-[400px] pointer-events-none z-0"></div>

    <div class="p-5 pb-40 max-w-md mx-auto relative z-10 overflow-x-hidden">
        
        {{-- HEADER --}}
        <header class="flex justify-between items-end mb-6 pt-4 animate-fade-in-up">
            <div>
                <p class="text-xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 shadow-[0_0_5px_#FCA5FF]"></span>
                    Laporan
                </p>
                <h1 class="text-3xl font-black text-white tracking-tight leading-none">Analitik</h1>
            </div>
            
            {{-- KOMPONEN KALENDER --}}
            <x-date-modal :action="route('analytics.index')" :start-date="$startDate" :end-date="$endDate" />
        </header>

        {{-- RINGKASAN --}}
        <div class="grid grid-cols-2 gap-3 mb-6 animate-fade-in-up delay-100">
            <div class="bg-gradient-to-br from-green-500/10 to-green-500/10 p-4 rounded-xl border border-white/5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 bg-green-500/10 rounded-bl-full blur-xl group-hover:bg-green-500/20 transition-colors"></div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-400 shadow-[0_0_5px_rgba(74,222,128,0.5)]"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pemasukan</p>
                </div>
                <p class="text-[15px] font-black text-green-400 tracking-tighter break-words relative z-10 leading-tight">
                    <span class="text-[10px] mr-0.5 opacity-70">+Rp</span>{{ number_format($totalIncome, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-gradient-to-br from-red-500/10 to-red-500/10 p-4 rounded-xl border border-white/5 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/10 rounded-bl-full blur-xl group-hover:bg-red-500/20 transition-colors"></div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-red-400 shadow-[0_0_5px_rgba(156,163,175,0.5)]"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pengeluaran</p>
                </div>
                <p class="text-[15px] font-black text-red-400 tracking-tighter break-words relative z-10 leading-tight">
                    <span class="text-red-400 text-[10px] mr-0.5 opacity-70">-Rp</span>{{ number_format($totalExpense, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- CHART CUMULATIVE --}}
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-gray-500/10 p-6 rounded-xl mb-8 shadow-[0_10px_30px_rgba(252,165,255,0.05)] animate-fade-in-up delay-200 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="flex justify-between items-start mb-6 relative z-10">
                <div>
                    <p class="text-xs font-bold text-white uppercase tracking-[0.2em] mb-1">Saldo Kumulatif</p>
                    <p class="text-[10px] text-gray-500 font-medium">Pergerakan total kekayaan</p>
                </div>
                <p class="text-lg font-black text-white tracking-tight bg-[#121212] px-3 py-1.5 rounded-xl border border-white/5 shadow-inner">
                    <span class="text-[10px] text-gray-500 mr-1">Rp</span>{{ number_format($cumulativeBalance, 0, ',', '.') }}
                </p>
            </div>
            <div class="w-full h-[140px] relative z-10">
                <canvas id="cumulativeChart"></canvas>
            </div>
        </div>

        {{-- ARUS KAS HARIAN --}}
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-gray-500/10 p-6 rounded-xl mb-8 shadow-[0_10px_30px_rgba(252,165,255,0.05)] animate-fade-in-up delay-300 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            
            <div class="flex justify-between items-center mb-6 relative z-10">
                <h2 class="text-xs font-bold text-white uppercase tracking-widest">Arus Kas</h2>
                
                {{-- Toggle Grouping --}}
                <div class="flex bg-[#121212] rounded-lg p-1 border border-white/5 shadow-inner">
                    <button id="btnHarian" onclick="renderBarChart('harian')" class="text-[9px] font-bold uppercase tracking-widest px-2.5 py-1.5 rounded-md transition-colors bg-[#FCA5FF] text-[#121212]">Hari</button>
                    <button id="btnMingguan" onclick="renderBarChart('mingguan')" class="text-[9px] font-bold uppercase tracking-widest px-2.5 py-1.5 rounded-md transition-colors text-gray-500 hover:text-white">Pekan</button>
                    <button id="btnBulanan" onclick="renderBarChart('bulanan')" class="text-[9px] font-bold uppercase tracking-widest px-2.5 py-1.5 rounded-md transition-colors text-gray-500 hover:text-white">Bulan</button>
                </div>
            </div>

            <div id="chartScrollBox" class="overflow-x-auto no-scrollbar pb-1" style="scroll-behavior: auto !important;">
                <div id="chartInnerContent" style="min-width: 100%; height: 180px; transition: min-width 0.3s ease;">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        {{-- TAB KATEGORI --}}
        <div class="flex items-center gap-2 mb-4 px-1 animate-fade-in-up delay-400">
            <h2 class="text-xs font-bold text-white uppercase tracking-widest">Rincian Kategori</h2>
            <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
        </div>
        <div class="flex bg-gray-900 border border-white/5 rounded-xl p-1.5 mb-5 shadow-inner animate-fade-in-up delay-400 relative">
            <div id="tabIndicator" class="absolute top-1.5 bottom-1.5 left-1.5 w-[calc(50%-0.375rem)] bg-gray-800 border border-white/5 shadow-md rounded-xl transition-all duration-300 ease-out z-0"></div>
            <button id="btnExpense" onclick="switchChart('expense')" class="relative z-10 flex-1 text-xs font-bold uppercase tracking-widest py-3 text-white transition-colors duration-300">Pengeluaran</button>
            <button id="btnIncome" onclick="switchChart('income')" class="relative z-10 flex-1 text-xs font-bold uppercase tracking-widest py-3 text-gray-500 transition-colors duration-300">Pemasukan</button>
        </div>

        {{-- DOUGHNUT CHART --}}
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-gray-500/10 p-6 rounded-xl mb-8 shadow-[0_10px_30px_rgba(252,165,255,0.05)] animate-fade-in-up delay-500 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center hidden rounded-xl z-20">
                <span class="w-12 h-12 bg-gray-800 rounded-xl flex items-center justify-center text-xl mb-3 border border-white/5">📭</span>
                <p class="text-xs font-bold text-white uppercase tracking-widest">Tidak Ada Data</p>
            </div>
            <div id="chartContainer" class="relative w-full h-56 mb-6">
                <canvas id="mainChart" class="relative z-10 w-full h-full"></canvas>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center pointer-events-none z-0">
                    <div class="w-[110px] h-[110px] rounded-full bg-gray-900 border border-white/5 shadow-inner flex flex-col items-center justify-center text-center px-1">
                        <span id="chartCenterLabel" class="text-[8px] text-gray-500 font-bold uppercase tracking-widest mb-1">Total</span>
                        <span id="chartCenterValue" class="text-sm font-black text-white tracking-tighter leading-tight w-full break-words">Rp 0</span>
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

    // FIX PENGECUALIAN FILTER: 
    // Menggunakan fallback '??' agar jika kamu belum mengupdate Controller, dia tidak akan Error (Hanya menggunakan data bulan ini). 
    // Jika Controller sudah di-update, dia akan otomatis pakai data ALL TIME!
    const rawBarLabels = @json($allDailyLabels ?? $dailyLabels);
    const rawBarIncome = @json($allDailyIncome ?? $dailyIncome);
    const rawBarExpense = @json($allDailyExpense ?? $dailyExpense);

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
            html += `<a href="/categories/${dataObj.ids[i]}" class="relative flex items-center justify-between bg-[#1A1A1A] p-3 rounded-xl border border-white/5 overflow-hidden group hover:border-[#FCA5FF]/30 transition-all duration-300">
                        <div class="absolute top-0 bottom-0 left-0 bg-gradient-to-r from-[${bgColor}]/20 to-transparent w-[${percentage}%] opacity-30"></div>
                        <div class="flex items-center gap-3 relative z-10 w-full">
                            <div class="w-1.5 h-6 rounded-full" style="background-color: ${bgColor};"></div>
                            <div class="w-8 h-8 rounded-xl bg-[#2A2A2A] flex items-center justify-center border border-white/5 overflow-hidden p-0.5">
                                ${rawIcon.includes('/') ? `<img src="/storage/${rawIcon}" class="w-full h-full object-cover">` : `<span class="text-sm">${rawIcon}</span>`}
                            </div>
                            <div class="flex-1 min-w-0 pr-2"><p class="text-[11px] font-bold text-gray-200 truncate">${label}</p><p class="text-[9px] text-gray-500 font-bold">${percentage}%</p></div>
                            <div class="text-right shrink-0"><span class="text-[11px] font-black text-white block">Rp ${val.toLocaleString('id-ID')}</span></div>
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

    // Fungsi Pembantu Untuk Scroll Paksa ke Ujung Kanan
    function forceScrollToRight() {
        const scrollBox = document.getElementById('chartScrollBox');
        if (scrollBox) {
            scrollBox.style.scrollBehavior = 'auto'; // Matikan smooth sementara
            scrollBox.scrollLeft = scrollBox.scrollWidth;
        }
    }

    window.renderBarChart = function(view) {
        let labels = [], incomes = [], expenses = [];

        if (view === 'harian') {
            labels = rawBarLabels; incomes = rawBarIncome; expenses = rawBarExpense;
        } 
        else if (view === 'mingguan') {
            let tempInc = 0, tempExp = 0;
            let startLabel = rawBarLabels[0];
            for(let i = 0; i < rawBarLabels.length; i++) {
                tempInc += rawBarIncome[i];
                tempExp += rawBarExpense[i];
                if ((i + 1) % 7 === 0 || i === rawBarLabels.length - 1) {
                    let endLabel = rawBarLabels[i];
                    labels.push(startLabel.split(' ')[0] + '-' + endLabel);
                    incomes.push(tempInc);
                    expenses.push(tempExp);
                    tempInc = 0; tempExp = 0;
                    if (i + 1 < rawBarLabels.length) startLabel = rawBarLabels[i + 1];
                }
            }
        } 
        else if (view === 'bulanan') {
            let currentMonth = '';
            let tempInc = 0, tempExp = 0;
            
            for(let i = 0; i < rawBarLabels.length; i++) {
                // FIX: Ekstrak nama bulan dan TAHUN agar presisi (Misal: "12 Apr 2026" -> ambil "Apr 2026")
                let parts = rawBarLabels[i].split(' ');
                let month = parts.length > 2 ? parts[1] + ' ' + parts[2] : (parts.length > 1 ? parts[1] : rawBarLabels[i]);
                
                if (i === 0) currentMonth = month; // Inisialisasi awal
                
                if (month !== currentMonth) {
                    labels.push(currentMonth);
                    incomes.push(tempInc);
                    expenses.push(tempExp);
                    currentMonth = month;
                    tempInc = 0; tempExp = 0;
                }
                tempInc += rawBarIncome[i];
                tempExp += rawBarExpense[i];
            }
            // Masukkan sisa bulan terakhir di loop
            if(currentMonth) { 
                labels.push(currentMonth); 
                incomes.push(tempInc); 
                expenses.push(tempExp); 
            }
        }

        const innerContent = document.getElementById('chartInnerContent');
        let calculatedWidth = view === 'harian' ? labels.length * 45 : labels.length * 60;
        innerContent.style.minWidth = `max(100%, ${calculatedWidth}px)`;

        destroyChart('bar');
        const ctxBar = document.getElementById('barChart')?.getContext('2d');
        if (ctxBar) {
            window.myCharts['bar'] = new Chart(ctxBar, {
                type: 'bar',
                data: { 
                    labels: labels, 
                    datasets: [
                        { label: 'In', data: incomes, backgroundColor: '#34D399', borderRadius: 4 }, 
                        { label: 'Out', data: expenses, backgroundColor: '#4B5563', borderRadius: 4 }
                    ] 
                },
                options: { 
                    responsive: true, maintainAspectRatio: false, 
                    plugins: { legend: { display: false } }, 
                    scales: { 
                        x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 9 } } }, 
                        y: { display: false } 
                    } 
                }
            });
        }

        const activeClass = "text-[9px] font-bold uppercase tracking-widest px-2.5 py-1.5 rounded-md transition-colors bg-[#FCA5FF] text-[#121212]";
        const inactiveClass = "text-[9px] font-bold uppercase tracking-widest px-2.5 py-1.5 rounded-md transition-colors text-gray-500 hover:text-white";
        
        document.getElementById('btnHarian').className = view === 'harian' ? activeClass : inactiveClass;
        document.getElementById('btnMingguan').className = view === 'mingguan' ? activeClass : inactiveClass;
        document.getElementById('btnBulanan').className = view === 'bulanan' ? activeClass : inactiveClass;

        // Failsafe eksekusi scroll paksa setelah render (Ditendang berkali-kali biar tidak balik ke awal)
        forceScrollToRight();
        setTimeout(forceScrollToRight, 50);
        setTimeout(forceScrollToRight, 200);
        setTimeout(forceScrollToRight, 800); 
    };

    function initCharts() {
        destroyChart('cumulative'); destroyChart('bar'); destroyChart('main');
        
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

        renderBarChart('harian');
        switchChart('expense');
    }

    document.addEventListener('livewire:navigated', initCharts);
    document.addEventListener('DOMContentLoaded', initCharts);
</script>
    <x-create-transaction />
    <x-bottom-nav />
</x-app-layout>