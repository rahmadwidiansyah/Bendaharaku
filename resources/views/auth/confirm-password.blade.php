<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfirmasi Password - Bendaharaku</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700,800&display=swap" rel="stylesheet" />
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #121212; color: #ffffff; overflow-x: hidden; }
        @keyframes slide-up { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        ::-webkit-scrollbar { width: 0px; background: transparent; }
    </style>
</head>
<body class="antialiased selection:bg-[#FCA5FF] selection:text-black relative flex justify-center min-h-screen">

    {{-- Efek Glow purple di tengah layar --}}
    <div class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

    {{-- CONTAINER MOBILE UTAMA --}}
    <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl shadow-2xl px-6 py-10">
        
        <div class="animate-slide-up w-full">
            <div class="text-center mb-8">
                <x-app-logo class="w-20 h-20 rounded-xl mx-auto mb-5" />
                <h1 class="text-3xl font-bold text-white tracking-tight">Area Aman</h1>
                <p class="text-xs text-gray-500 uppercase tracking-widest mt-2 font-bold">Verifikasi Identitas Anda</p>
            </div>

            <p class="text-[11px] text-gray-400 text-center mb-8 leading-relaxed px-2">
                Ini adalah area aplikasi yang aman. Harap konfirmasi password Anda sebelum melanjutkan ke halaman berikutnya.
            </p>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password Anda</label>
                    <input type="password" name="password" required autocomplete="current-password" autofocus
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-xl p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner">
                    @error('password') <span class="text-xs text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform mt-6">
                    Konfirmasi Akses
                </button>
            </form>
        </div>
        
    </div>
</body>
</html>