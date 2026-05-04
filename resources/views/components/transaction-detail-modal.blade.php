{{-- Modal Detail Transaksi Reusable --}}
<div id="detailModal" class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 transition-opacity">
    <div class="w-full max-w-sm bg-[#121212] rounded-xl border border-[#262626] p-6 shadow-2xl animate-pop-in relative">
        
        <button onclick="closeDetailModal()" class="absolute top-4 right-4 w-8 h-8 bg-[#1A1A1A] border border-[#333] rounded-full flex items-center justify-center text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex flex-col items-center mb-6 mt-2">
            <div id="modIcon" class="w-16 h-16 rounded-xl bg-[#1A1A1A] flex items-center justify-center text-3xl border border-[#333] shadow-inner mb-3 overflow-hidden p-1"></div>
            <p id="modCategory" class="text-xl font-bold text-white text-center"></p>
            <p id="modDate" class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1 text-center"></p>
        </div>

        <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-5 text-center mb-5">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Nominal</p>
            <h2 id="modAmount" class="text-3xl font-bold tracking-tight"></h2>
        </div>

        <div class="space-y-4 mb-6 px-1 text-xs">
            <div class="flex justify-between items-center border-b border-[#262626] pb-3 text-white">
                <span class="text-gray-500 uppercase font-bold tracking-widest" style="font-size: 8px;">Dompet</span>
                <div class="text-right flex items-center gap-2">
                    <span id="modSource" class="font-bold text-gray-300"></span>
                    <svg class="w-3 h-3 text-[#FCA5FF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    <span id="modDest" class="font-bold"></span>
                </div>
            </div>
            <div id="wrapSubject" class="flex justify-between items-center border-b border-[#262626] pb-3 text-white">
                <span class="text-gray-500 uppercase font-bold tracking-widest" style="font-size: 8px;">Pelaku</span>
                <span id="modSubject" class="font-bold"></span>
            </div>
            <div class="flex justify-between items-start text-white">
                <span class="text-gray-500 uppercase font-bold tracking-widest" style="font-size: 8px;">Catatan</span>
                <span id="modNotes" class="text-right italic text-gray-400"></span>
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <a href="#" id="modEditBtn" class="flex-1 bg-[#1A1A1A] border border-[#333] py-3 rounded-xl flex items-center justify-center gap-2 text-gray-300 text-xs font-bold uppercase" wire:navigate>Edit</a>
            <form id="modDeleteForm" action="#" method="POST" class="flex-1" onsubmit="return confirm('Hapus transaksi ini?');">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-[#1A1A1A] border border-[#333] py-3 rounded-xl text-red-500 text-xs font-bold uppercase">Hapus</button>
            </form>
        </div>
    </div>
</div>

<script>
    function openDetailModal(btn) {
        const data = btn.dataset;
        const modIcon = document.getElementById('modIcon');

        // Logic Ikon (Emoji vs Gambar)
        if (data.isImage === 'true') {
            modIcon.innerHTML = `<img src="${data.iconUrl}" class="w-full h-full object-cover rounded-xl">`;
        } else {
            modIcon.innerHTML = data.icon;
        }

        document.getElementById('modCategory').innerText = data.category;
        document.getElementById('modDate').innerText = data.date + ' • ' + data.time;

        const amountEl = document.getElementById('modAmount');
        if (data.type === 'Income') {
            amountEl.innerText = '+ Rp ' + data.amount;
            amountEl.className = 'text-3xl font-bold tracking-tight text-green-400';
        } else {
            amountEl.innerText = '- Rp ' + data.amount;
            amountEl.className = 'text-3xl font-bold tracking-tight text-white';
        }

        document.getElementById('modSource').innerText = data.source;
        document.getElementById('modDest').innerText = data.dest;

        const wrapSub = document.getElementById('wrapSubject');
        if (data.subject && data.subject !== '-') {
            wrapSub.style.display = 'flex';
            document.getElementById('modSubject').innerText = data.subject;
        } else {
            wrapSub.style.display = 'none';
        }

        document.getElementById('modNotes').innerText = data.notes || 'Tidak ada catatan.';
        document.getElementById('modEditBtn').href = `/transactions/${data.id}/edit`;
        document.getElementById('modDeleteForm').action = `/transactions/${data.id}`;

        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('detailModal');
        if (e.target === modal) closeDetailModal();
    });
</script>

<style>
    @keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    .animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>