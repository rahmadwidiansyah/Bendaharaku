@props(['action', 'startDate', 'endDate'])

{{-- TOMBOL PEMICU DI HEADER --}}
<button type="button" onclick="toggleDateModal()" class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-gray-400 hover:text-white rounded-xl px-4 flex items-center justify-center active:scale-95 transition-all shadow-md relative h-[48px] z-30">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
    @if($startDate != \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'))
        <span class="absolute top-2 right-3 w-2 h-2 bg-purple-500 rounded-full"></span>
    @endif
</button>

{{-- MODAL CENTER POP-UP --}}
<div id="dateModal" class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm hidden flex items-center justify-center transition-all duration-300 opacity-0 pointer-events-none p-4">
    <div class="absolute inset-0 w-full h-full pointer-events-auto" onclick="toggleDateModal()"></div>
    
    <div id="dateModalContent" class="w-full max-w-sm mx-auto bg-gradient-to-b from-gray-900 to-gray-800 rounded-2xl border border-white/10 p-6 transform scale-95 transition-transform duration-300 relative z-[10000] pointer-events-auto shadow-2xl">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-sm font-black text-white uppercase tracking-widest">Rentang Waktu</h3>
            <button type="button" onclick="toggleDateModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 active:scale-90 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        
        <form action="{{ $action }}" method="GET" class="space-y-5">
            {{ $slot }}
            
            <div class="grid grid-cols-2 gap-3 text-left">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-purple-400 uppercase tracking-widest pl-1">Dari</label>
                    <input type="date" name="start_date" id="modal_start_date" value="{{ $startDate }}" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3 text-xs" style="color-scheme: dark;">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-purple-400 uppercase tracking-widest pl-1">Sampai</label>
                    <input type="date" name="end_date" id="modal_end_date" value="{{ $endDate }}" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3 text-xs" style="color-scheme: dark;">
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-2 pt-2">
                {{-- TOMBOL DIUBAH MENJADI TAHUN INI --}}
                <button type="button" onclick="setQuickDate('thisYear')" class="bg-[#1A1A1A] text-xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Tahun Ini</button>
                <button type="button" onclick="setQuickDate('thisMonth')" class="bg-[#1A1A1A] text-xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Bulan Ini</button>
                <button type="button" onclick="setQuickDate('lastMonth')" class="bg-[#1A1A1A] text-xs font-bold text-gray-400 py-3 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Bulan Lalu</button>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-br from-purple-600 to-purple-500 text-white font-black text-xs uppercase py-3.5 rounded-xl shadow-[0_0_15px_rgba(168,85,247,0.4)] active:scale-95 transition-all mt-2">Terapkan Filter</button>
        </form>
    </div>
</div>

<script>
    if (typeof window.toggleDateModal !== 'function') {
        window.toggleDateModal = function() {
            const m = document.getElementById('dateModal'); 
            const c = document.getElementById('dateModalContent');
            if(!m || !c) return;
            
            if (m.classList.contains('hidden')) {
                m.classList.remove('hidden'); void m.offsetWidth;
                m.classList.remove('opacity-0', 'pointer-events-none');
                m.classList.add('opacity-100', 'pointer-events-auto');
                c.classList.replace('scale-95', 'scale-100');
            } else {
                m.classList.add('opacity-0', 'pointer-events-none');
                m.classList.remove('opacity-100', 'pointer-events-auto');
                c.classList.replace('scale-100', 'scale-95');
                setTimeout(() => m.classList.add('hidden'), 300);
            }
        };
        
        window.setQuickDate = function(type) {
            let s, e, t = new Date(); 
            const f = (d) => [d.getFullYear(), String(d.getMonth()+1).padStart(2,'0'), String(d.getDate()).padStart(2,'0')].join('-');
            
            // LOGIKA DIUBAH KE TAHUN INI
            if(type==='thisYear'){ 
                s = new Date(t.getFullYear(), 0, 1); // 1 Januari tahun ini
                e = new Date(t.getFullYear(), 11, 31); // 31 Desember tahun ini
            }
            else if(type==='thisMonth'){ 
                s = new Date(t.getFullYear(), t.getMonth(), 1); 
                e = new Date(t.getFullYear(), t.getMonth()+1, 0); 
            }
            else if(type==='lastMonth'){ 
                s = new Date(t.getFullYear(), t.getMonth()-1, 1); 
                e = new Date(t.getFullYear(), t.getMonth(), 0); 
            }
            
            document.getElementById('modal_start_date').value = f(s); 
            document.getElementById('modal_end_date').value = f(e);
        };
    }
</script>