<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative animate-slide-up opacity-0" style="animation-delay: 50ms;">
        
        {{-- Header: Sleek --}}
        <header class="flex justify-between items-center mb-8 pt-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Tambah Dompet</h1>
            </div>
            <a href="{{ route('dashboard') }}" wire:navigate class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#333] flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white hover:border-gray-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </header>

        {{-- TABS: LIQUID & INVESTMENT --}}
        <div class="grid grid-cols-2 gap-2 mb-8 p-1.5 bg-[#1A1A1A] border border-[#333] rounded-xl animate-slide-up opacity-0" style="animation-delay: 100ms;">
            <button type="button" id="tabLiquid" onclick="setGroup('Liquid')" class="w-full text-xs font-semibold py-3 rounded-xl bg-[#262626] text-[#FCA5FF] shadow-sm transition-all border border-[#444]">
                Liquid
            </button>
            <button type="button" id="tabAsset" onclick="setGroup('Asset')" class="w-full text-xs font-semibold py-3 rounded-xl text-gray-400 hover:text-white transition-all border border-transparent">
                Investment
            </button>
        </div>

        <form action="{{ route('wallets.store') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="group_type" id="group_type" value="Liquid">
            
            {{-- INPUT: SALDO AWAL --}}
            <div class="flex flex-col animate-slide-up opacity-0 relative z-10" style="animation-delay: 150ms;">
                <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Saldo Awal</label>
                <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                    <span class="text-base font-bold text-[#FCA5FF] mr-3 opacity-80">Rp</span>
                    <input type="text" id="display_amount" inputmode="numeric" required placeholder="0"
                        class="w-full bg-transparent border-none text-white p-0 text-xl font-bold placeholder-gray-600 focus:ring-0 focus:outline-none caret-[#FCA5FF]">
                    <input type="hidden" name="balance" id="raw_amount">
                </div>
            </div>

            {{-- FLEX LAYOUT: ICON & NAME (Z-Index Dipertinggi: 50) --}}
            <div class="flex gap-3 items-end animate-slide-up opacity-0 relative z-50" style="animation-delay: 200ms;">
                {{-- PEMANGGILAN KOMPONEN EMOJI PICKER --}}
                <div class="flex-none">
                    <x-emoji-picker id="icon" default="💳" />
                </div>

                {{-- NAME --}}
                <div class="flex-1 flex flex-col justify-end">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Dompet</label>
                    <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                        <input type="text" name="name" required placeholder="Contoh: BCA Utama" 
                            class="w-full bg-transparent border-none text-white p-0 text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- KEYWORD AI (Z-Index Lebih Rendah: 40) --}}
            <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 250ms;">
                <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                <div class="bg-[#1A1A1A] border border-[#333] rounded-xl p-4 group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all">
                    <input type="text" name="keyword" placeholder="Contoh: bca, transfer, mbanking..." 
                        class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                </div>
                <p class="text-xs text-gray-500 mt-2 ml-1 italic">* Digunakan untuk deteksi otomatis oleh sistem AI.</p>
            </div>

            {{-- SUBMIT --}}
            <div class="pt-4 animate-slide-up opacity-0 relative z-30" style="animation-delay: 300ms;">
                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm tracking-wide py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.15)] hover:shadow-[0_0_25px_rgba(252,165,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                    Simpan Dompet
                </button>
            </div>
        </form>
    </div>

    {{-- CSS Khusus Animasi --}}
    <style>
        input:focus, input:active { outline: none !important; box-shadow: none !important; }
        
        @keyframes slideUpFade {
            0% { opacity: 0; transform: translateY(20px); }
            /* Mengubah translateY(0) menjadi none untuk melepaskan elemen dari kutukan Stacking Context */
            100% { opacity: 1; transform: none; } 
        }
        .animate-slide-up {
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <script>
        // Definisi fungsi global agar tidak hilang saat navigasi Livewire
        window.setGroup = function(group) {
            document.getElementById('group_type').value = group;
            const tabLiq = document.getElementById('tabLiquid');
            const tabAss = document.getElementById('tabAsset');
            
            const activeClass = "w-full text-xs font-semibold py-3 rounded-xl bg-[#262626] text-[#FCA5FF] shadow-sm transition-all border border-[#444]";
            const inactiveClass = "w-full text-xs font-semibold py-3 rounded-xl text-gray-400 hover:text-white transition-all border border-transparent";
            
            if (group === 'Liquid') {
                tabLiq.className = activeClass;
                tabAss.className = inactiveClass;
            } else {
                tabAss.className = activeClass;
                tabLiq.className = inactiveClass;
            }
        }

        // Livewire Safe Initialization
        function initWalletForm() {
            const displayAmount = document.getElementById('display_amount');
            const rawAmount = document.getElementById('raw_amount');

            if(displayAmount) {
                // Hapus event listener lama jika ada (mencegah duplicate saat Livewire refresh)
                displayAmount.replaceWith(displayAmount.cloneNode(true));
                const newDisplayAmount = document.getElementById('display_amount');

                newDisplayAmount.addEventListener('input', function(e) {
                    let val = this.value.replace(/\D/g, ''); 
                    rawAmount.value = val; 
                    this.value = val ? new Intl.NumberFormat('id-ID').format(parseInt(val, 10)) : '';
                });
            }
        }

        // Jalankan saat load normal & saat Livewire navigasi selesai
        document.addEventListener('DOMContentLoaded', initWalletForm);
        document.addEventListener('livewire:navigated', initWalletForm);
    </script>
</x-app-layout>