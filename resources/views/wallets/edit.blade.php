<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative animate-slide-up opacity-0" style="animation-delay: 50ms;">
        
        {{-- Header --}}
        <header class="flex justify-between items-center mb-8 pt-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Edit Dompet</h1>
            </div>
            {{-- Tombol Close/Back --}}
            <a href="{{ route('wallets.show', $wallet) }}" wire:navigate class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#333] flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white hover:border-gray-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </header>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-900/20 border border-red-500/30 text-red-200 rounded-2xl text-xs font-medium animate-slide-up opacity-0" style="animation-delay: 100ms;">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('wallets.update', $wallet) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            {{-- INPUT: SALDO SAAT INI (Standar Form, Disesuaikan dengan Tambah Dompet) --}}
            <div class="flex flex-col animate-slide-up opacity-0 relative z-10" style="animation-delay: 150ms;">
                <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Saldo Saat Ini</label>
                <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-2xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                    <span class="text-base font-bold text-[#FCA5FF] mr-3 opacity-80">Rp</span>
                    <input type="text" id="display_amount" inputmode="numeric" required 
                        value="{{ number_format($wallet->balance, 0, ',', '.') }}"
                        class="w-full bg-transparent border-none text-white p-0 text-xl font-bold placeholder-gray-600 focus:ring-0 focus:outline-none caret-[#FCA5FF]">
                    <input type="hidden" name="balance" id="raw_amount" value="{{ (int)$wallet->balance }}">
                </div>
                <p class="text-[10px] text-gray-500 mt-2 ml-1 italic">* Ubah manual jika ada selisih saldo.</p>
            </div>

            {{-- FLEX LAYOUT: ICON & NAME (Z-Index Dipertinggi: 50) --}}
            <div class="flex gap-3 items-end animate-slide-up opacity-0 relative z-50" style="animation-delay: 200ms;">
                {{-- REUSABLE EMOJI PICKER --}}
                <div class="flex-none">
                    <x-emoji-picker id="icon" :default="$wallet->icon ?? '💳'" />
                </div>

                {{-- NAME --}}
                <div class="flex-1 flex flex-col justify-end">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Dompet</label>
                    <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-2xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                        <input type="text" name="name" value="{{ $wallet->name }}" required 
                            class="w-full bg-transparent border-none text-white p-0 text-base font-medium focus:ring-0 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- KEYWORD AI (Z-Index Lebih Rendah: 40) --}}
            <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 250ms;">
                <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                <div class="bg-[#1A1A1A] border border-[#333] rounded-2xl p-4 group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all">
                    <input type="text" name="keyword" value="{{ $wallet->keyword }}" placeholder="Contoh: bca, transfer, mbanking..." 
                        class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                </div>
            </div>

            {{-- ACTIONS (Update Button) - Z-Index 30 --}}
            <div class="pt-4 space-y-3 animate-slide-up opacity-0 relative z-30" style="animation-delay: 300ms;">
                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm tracking-wide py-4 rounded-2xl shadow-[0_0_20px_rgba(252,165,255,0.15)] hover:shadow-[0_0_25px_rgba(252,165,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                    Update Dompet
                </button>
            </div>
        </form>

        {{-- FORM HAPUS - Z-Index 20 --}}
        <form action="{{ route('wallets.destroy', $wallet) }}" method="POST" class="mt-4 animate-slide-up opacity-0 relative z-20" style="animation-delay: 350ms;" onsubmit="return confirm('Yakin ingin menghapus dompet ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full bg-transparent border border-red-500/20 text-red-500/70 font-medium text-xs uppercase tracking-widest py-4 rounded-2xl hover:bg-red-500/10 active:scale-95 transition-all">
                Hapus Dompet
            </button>
        </form>
    </div>

    {{-- CSS Khusus Animasi --}}
    <style>
        input:focus, input:active { outline: none !important; box-shadow: none !important; }
        
        @keyframes slideUpFade {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: none; } 
        }
        .animate-slide-up {
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <script>
        // Livewire Safe Initialization untuk Form Edit
        function initEditWalletForm() {
            const displayAmount = document.getElementById('display_amount');
            const rawAmount = document.getElementById('raw_amount');

            if (displayAmount) {
                // Bersihkan listener lama untuk mencegah bug di Livewire
                displayAmount.replaceWith(displayAmount.cloneNode(true));
                const newDisplayAmount = document.getElementById('display_amount');

                newDisplayAmount.addEventListener('input', function(e) {
                    // Hapus karakter selain angka
                    let val = this.value.replace(/\D/g, ''); 
                    
                    // Update hidden input
                    rawAmount.value = val; 
                    
                    // Format tampilan
                    this.value = val ? new Intl.NumberFormat('id-ID').format(parseInt(val, 10)) : '';
                });
            }
        }

        // Jalankan saat load pertama kali & setiap kali Livewire selesai bernavigasi
        document.addEventListener('DOMContentLoaded', initEditWalletForm);
        document.addEventListener('livewire:navigated', initEditWalletForm);
    </script>
</x-app-layout>