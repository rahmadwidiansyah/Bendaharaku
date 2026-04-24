@props(['action', 'startDate', 'endDate'])

{{-- TOMBOL PEMICU DI HEADER --}}
<button type="button" onclick="toggleDateModal()" class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-gray-400 hover:text-white rounded-xl px-4 flex items-center justify-center active:scale-95 transition-all shadow-md relative h-[48px] z-30">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
    @if($startDate != \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'))
        <span class="absolute top-2 right-3 w-2 h-2 bg-purple-500 rounded-full"></span>
    @endif
</button>

{{-- MODAL BOTTOM SHEET (FIXED DALAM BOX) --}}
<div id="dateModal" class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-sm hidden flex flex-col justify-end transition-all duration-300 opacity-0 pointer-events-none">
    <div class="absolute inset-0 w-full h-full pointer-events-auto" onclick="toggleDateModal()"></div>
    <div id="dateModalContent" class="w-full max-w-md mx-auto bg-gradient-to-b from-gray-900 to-gray-800 rounded-t-[1rem] border-t border-white/10 p-6 pb-12 transform translate-y-full transition-transform duration-300 relative z-[10000] pointer-events-auto shadow-[0_-10px_50px_rgba(0,0,0,0.8)]">
        <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-6"></div>
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-sm font-black text-white uppercase tracking-widest">Rentang Waktu</h3>
            <button type="button" onclick="toggleDateModal()" class="w-9 h-9 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-gray-400 active:scale-90 transition-all"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
        <form action="{{ $action }}" method="GET" class="space-y-6">
            {{ $slot }}
            <div class="grid grid-cols-2 gap-4 text-left">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-purple-400 uppercase tracking-widest pl-1">Dari</label>
                    <input type="date" name="start_date" id="modal_start_date" value="{{ $startDate }}" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-xs" style="color-scheme: dark;">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-purple-400 uppercase tracking-widest pl-1">Sampai</label>
                    <input type="date" name="end_date" id="modal_end_date" value="{{ $endDate }}" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-xs" style="color-scheme: dark;">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3 py-5">
                <button type="button" onclick="setQuickDate('7days')" class="bg-gradient-to-br from-gray-900 to-gray-800 text-xs font-bold text-gray-400 py-5 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">7 Hari</button>
                <button type="button" onclick="setQuickDate('thisMonth')" class="bg-gradient-to-br from-gray-900 to-gray-800 text-xs font-bold text-gray-400 py-5 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Bulan Ini</button>
                <button type="button" onclick="setQuickDate('lastMonth')" class="bg-gradient-to-br from-gray-900 to-gray-800 text-xs font-bold text-gray-400 py-5 rounded-xl border border-white/10 uppercase hover:text-white transition-colors">Bulan Lalu</button>
            </div>
            <button type="submit" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 text-white font-black text-xs uppercase py-4 rounded-xl border border-white/10 shadow-lg active:scale-95 transition-all">Terapkan Filter</button>
        </form>
    </div>
</div>

<script>
    if (typeof window.toggleDateModal !== 'function') {
        window.toggleDateModal = function() {
            const m = document.getElementById('dateModal'); const c = document.getElementById('dateModalContent');
            if(!m || !c) return;
            if (m.classList.contains('hidden')) {
                m.classList.remove('hidden'); void m.offsetWidth;
                m.classList.remove('opacity-0', 'pointer-events-none');
                m.classList.add('opacity-100', 'pointer-events-auto');
                c.classList.replace('translate-y-full', 'translate-y-0');
            } else {
                m.classList.add('opacity-0', 'pointer-events-none');
                m.classList.remove('opacity-100', 'pointer-events-auto');
                c.classList.replace('translate-y-0', 'translate-y-full');
                setTimeout(() => m.classList.add('hidden'), 300);
            }
        };
        window.setQuickDate = function(type) {
            let s, e, t = new Date(); const f = (d) => [d.getFullYear(), String(d.getMonth()+1).padStart(2,'0'), String(d.getDate()).padStart(2,'0')].join('-');
            if(type==='7days'){ e=new Date(); s=new Date(); s.setDate(e.getDate()-6); }
            else if(type==='thisMonth'){ s=new Date(t.getFullYear(), t.getMonth(), 1); e=new Date(t.getFullYear(), t.getMonth()+1, 0); }
            else if(type==='lastMonth'){ s=new Date(t.getFullYear(), t.getMonth()-1, 1); e=new Date(t.getFullYear(), t.getMonth(), 0); }
            document.getElementById('modal_start_date').value = f(s); document.getElementById('modal_end_date').value = f(e);
        };
    }
</script>