<x-app-layout>
    {{-- Glow Background ala Login Page --}}
    <div class="fixed top-[-10%] left-[50%] -translate-x-1/2 w-[400px] h-[400px] bg-[#FCA5FF] blur-[150px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

    <div class="p-5 pb-32 max-w-md mx-auto relative z-10 animate-slide-up">
        
        <header class="flex justify-between items-center mb-10 pt-4">
            <h1 class="text-3xl font-bold text-white tracking-tight">Profil Saya</h1>
            <a href="{{ route('dashboard') }}" wire:navigate class="w-12 h-12 rounded-xl bg-[#1A1A1A] border border-[#262626] flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-inner">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </a>
        </header>

        {{-- FORM UTAMA (FOTO, NAMA, EMAIL, GOOGLE) --}}
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf @method('patch')

            {{-- LOGIKA AVATAR --}}
            @php
                $avatar = $user->avatar;
                $isUrl = $avatar && Str::startsWith($avatar, ['http://', 'https://']);
                $hasAvatar = !empty($avatar) && (Str::contains($avatar, ['.png', '.jpg', '.jpeg', '.webp', '/']) || $isUrl);
                $avatarSrc = $hasAvatar 
                    ? ($isUrl ? $avatar : asset('storage/'.$avatar)) 
                    : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=1A1A1A&color=FCA5FF&size=128&bold=true';
            @endphp

            {{-- BAGIAN FOTO PROFIL MANUAL --}}
            <div class="flex flex-col items-center mb-8">
                <div class="relative mb-5 group">
                    <div class="absolute inset-0 bg-[#FCA5FF]/20 rounded-full blur-xl scale-90 group-hover:scale-110 transition-all duration-300"></div>
                    
                    <div class="relative w-28 h-28 rounded-full border-4 border-[#1A1A1A] p-0.5 bg-[#121212] shadow-2xl overflow-hidden flex items-center justify-center group-hover:border-[#FCA5FF] transition-colors duration-300">
                        <img id="avatarPreview" src="{{ $avatarSrc }}" alt="{{ $user->name }}" class="w-full h-full object-cover rounded-full">
                        
                        <label for="avatar_file" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer text-white/90 backdrop-blur-sm">
                            <svg class="w-7 h-7 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="text-[9px] font-bold uppercase tracking-widest">Pilih Foto</span>
                        </label>
                    </div>
                </div>
                
                {{-- Input File Hidden --}}
                <input type="file" name="avatar_file" id="avatar_file" accept="image/*" class="hidden" onchange="previewAvatar(event)">
                <x-input-error class="mt-2 text-[10px] text-red-500 font-bold" :messages="$errors->get('avatar_file')" />
            </div>

            {{-- TOMBOL GOOGLE --}}
            @if(!$user->google_id)
                <a href="{{ route('google.login') }}" class="flex items-center justify-center gap-3 bg-[#1A1A1A] text-white border border-[#333] text-[11px] font-bold px-6 py-4 rounded-xl uppercase tracking-widest active:scale-95 transition-all hover:border-gray-500 mb-6">
                    <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="w-4 h-4">
                    Hubungkan Akun Google
                </a>
            @else
                <div class="text-center py-3.5 px-4 bg-green-500/10 border border-green-500/20 rounded-xl text-[10px] text-green-400 font-bold uppercase tracking-widest flex items-center justify-center gap-2 mb-6">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                    Terkoneksi dengan Google
                </div>
            @endif

            {{-- FORM INFORMASI AKUN --}}
            <div class="space-y-5">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all">
                    <x-input-error class="mt-2 text-[10px] text-red-500 font-bold" :messages="$errors->get('name')" />
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all">
                    <x-input-error class="mt-2 text-[10px] text-red-500 font-bold" :messages="$errors->get('email')" />
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">WhatsApp</label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" placeholder="0812..."
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all">
                    <x-input-error class="mt-2 text-[10px] text-red-500 font-bold" :messages="$errors->get('whatsapp_number')" />
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Telegram</label>
                    <input type="text" name="telegram_id" value="{{ old('telegram_id', $user->telegram_id) }}" placeholder="@username"
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all">
                    <x-input-error class="mt-2 text-[10px] text-red-500 font-bold" :messages="$errors->get('telegram_id')" />
                </div>

                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform mt-6">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        {{-- FORM PASSWORD (TERPISAH) --}}
        <div class="mt-10 mb-10 pt-8 border-t border-[#262626]">
            <h3 class="text-lg font-bold text-white mb-6 tracking-tight">Ubah Password</h3>
            
            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf @method('put')
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all">
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2 text-[10px] text-red-500 font-bold" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password Baru</label>
                    <input type="password" name="password" required class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all">
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2 text-[10px] text-red-500 font-bold" />
                </div>
                <button type="submit" class="w-full bg-[#1A1A1A] text-white border border-[#333] font-bold text-[11px] uppercase tracking-widest py-4 rounded-xl hover:border-[#FCA5FF] active:scale-95 transition-all mt-2">
                    Update Password
                </button>
            </form>
        </div>

        {{-- LOGOUT (STANDALONE) --}}
        <form method="POST" action="{{ route('logout') }}" class="mb-10">
            @csrf
            <button type="submit" class="w-full bg-[#1A1A1A] text-gray-400 border border-[#333] text-[11px] font-bold px-6 py-4 rounded-xl uppercase tracking-widest active:scale-95 transition-all hover:text-white hover:border-gray-500">
                🚪 Keluar dari Aplikasi
            </button>
        </form>

        {{-- ZONA BERBAHAYA (SEMBUNYI DALAM DETAILS/ACCORDION) --}}
        <details class="group border-t border-[#262626] pt-6">
            <summary class="list-none cursor-pointer text-[10px] text-gray-500 hover:text-red-400 font-bold uppercase tracking-widest flex items-center justify-center gap-2 transition-colors select-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span class="group-open:hidden">Tampilkan Zona Berbahaya</span>
                <span class="hidden group-open:block">Sembunyikan Zona Berbahaya</span>
            </summary>
            
            <div class="mt-6 bg-red-950/20 border border-red-900/30 rounded-xl p-6 shadow-sm animate-slide-up">
                <h4 class="text-red-400 font-bold text-sm mb-2">Hapus Akun Permanen</h4>
                <p class="text-[10px] text-gray-400 mb-5 leading-relaxed">Setelah dihapus, semua data keuangan, histori, dan pengaturan kamu akan musnah dan tidak dapat dipulihkan.</p>
                <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('YAKIN HAPUS PERMANEN? Semua data keuangan kamu akan hilang.');">
                    @csrf @method('delete')
                    <button type="submit" class="w-full bg-red-900/40 border border-red-900/50 text-red-200 font-bold text-[10px] uppercase tracking-widest py-3.5 rounded-xl active:scale-95 transition-all hover:bg-red-600 hover:text-white">
                        Ya, Hapus Akun Saya
                    </button>
                </form>
            </div>
        </details>

    </div>

    <x-bottom-nav />

    {{-- SCRIPT UNTUK PREVIEW FOTO MANUAL & ANIMASI --}}
    <script>
        function previewAvatar(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(){
                    const output = document.getElementById('avatarPreview');
                    output.src = reader.result;
                    output.animate([{ opacity: 0.5 }, { opacity: 1 }], { duration: 300, fill: "forwards" });
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

    {{-- CSS TAMBAHAN UNTUK ANIMASI --}}
    <style>
        @keyframes slide-up { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        /* Sembunyikan default panah details di safari/chrome */
        details > summary::-webkit-details-marker { display: none; }
    </style>
</x-app-layout>