<x-app-layout>
    <div class="p-5 pb-32 max-w-md mx-auto relative animate-slide-up opacity-0" style="animation-delay: 50ms;">
        
        {{-- Header --}}
        <header class="flex justify-between items-center mb-8 pt-4">
            <div>
                <p class="text-xs text-[#FCA5FF] font-semibold mb-1 uppercase tracking-wider">Vault</p>
                <h1 class="text-2xl font-bold text-white tracking-tight">Edit Kategori</h1>
            </div>
            <a href="{{ route('categories.index') }}" wire:navigate class="w-10 h-10 rounded-full bg-[#1A1A1A] border border-[#333] flex items-center justify-center text-gray-400 active:scale-90 transition-all shadow-md hover:text-white hover:border-gray-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </a>
        </header>

        <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            {{-- PILIH TIPE (Pill Select) - Z-Index 10 --}}
            <div class="animate-slide-up opacity-0 relative z-10" style="animation-delay: 100ms;">
                <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Tipe Kategori</label>
                <div class="grid grid-cols-2 gap-2 p-1.5 bg-[#1A1A1A] border border-[#333] rounded-2xl shadow-inner">
                    @foreach($types as $type)
                        <label class="cursor-pointer">
                            <input type="radio" name="type_id" value="{{ $type->id }}" class="hidden peer" {{ $category->type_id == $type->id ? 'checked' : '' }} required>
                            <div class="text-xs font-semibold py-3 text-center rounded-xl transition-all border border-transparent text-gray-400 peer-checked:bg-[#262626] peer-checked:text-[#FCA5FF] peer-checked:border-[#444] peer-checked:shadow-sm">
                                {{ $type->name == 'Income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- FLEX LAYOUT: ICON & NAME (Z-Index 50 agar popup tidak ketimpa elemen bawahnya) --}}
            <div class="flex gap-3 items-end animate-slide-up opacity-0 relative z-50" style="animation-delay: 150ms;">
                
                {{-- PEMANGGILAN KOMPONEN EMOJI PICKER DENGAN DATA LAMA --}}
                <div class="flex-none">
                    <x-emoji-picker id="icon" :default="$category->icon ?? '📁'" />
                </div>

                {{-- NAMA KATEGORI --}}
                <div class="flex-1 flex flex-col justify-end">
                    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Nama Kategori</label>
                    <div class="h-[60px] bg-[#1A1A1A] border border-[#333] rounded-2xl px-5 flex items-center group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                        <input type="text" name="category_name" value="{{ $category->category_name }}" required placeholder="Contoh: Makan Siang..." 
                            class="w-full bg-transparent border-none text-white p-0 text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- KEYWORD AI (Z-Index 40) --}}
            <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 200ms;">
                <label class="block text-sm font-medium text-gray-300 mb-2 ml-1">Keyword AI (Pisahkan Koma)</label>
                <div class="bg-[#1A1A1A] border border-[#333] rounded-2xl p-4 group focus-within:border-[#FCA5FF] focus-within:ring-1 focus-within:ring-[#FCA5FF] transition-all shadow-inner">
                    <input type="text" name="keyword" value="{{ $category->keyword }}" placeholder="Contoh: mcd, kfc, warkop, bensin..." 
                        class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                </div>
                <p class="text-[10px] text-gray-500 mt-2 ml-1 italic">* Digunakan untuk deteksi otomatis oleh sistem AI.</p>
            </div>

            {{-- ACTIONS (Update Button) - Z-Index 30 --}}
            <div class="pt-4 space-y-3 animate-slide-up opacity-0 relative z-30" style="animation-delay: 250ms;">
                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm tracking-wide py-4 rounded-2xl shadow-[0_0_20px_rgba(252,165,255,0.15)] hover:shadow-[0_0_25px_rgba(252,165,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                    Update Kategori
                </button>
            </div>
        </form>

        {{-- FORM HAPUS - Z-Index 20 --}}
        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="mt-4 animate-slide-up opacity-0 relative z-20" style="animation-delay: 300ms;" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full bg-transparent border border-red-500/20 text-red-500/70 font-medium text-xs uppercase tracking-widest py-4 rounded-2xl hover:bg-red-500/10 active:scale-95 transition-all">
                Hapus Kategori
            </button>
        </form>
    </div>

    {{-- CSS Khusus Animasi & Input Reset --}}
    <style>
        input:focus, input:active { outline: none !important; box-shadow: none !important; }
        
        @keyframes slideUpFade {
            0% { opacity: 0; transform: translateY(20px); }
            /* Mengubah ke none agar elemen bebas dari batasan stacking context setelah animasi */
            100% { opacity: 1; transform: none; } 
        }
        .animate-slide-up {
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</x-app-layout>