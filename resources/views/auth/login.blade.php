<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Bendaharaku</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700,800&display=swap" rel="stylesheet" />
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #121212; color: #ffffff; overflow-x: hidden; }
        @keyframes slide-up { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-up { animation: slide-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        /* Sembunyikan scrollbar bawaan */
        ::-webkit-scrollbar { width: 0px; background: transparent; }
    </style>
</head>
<body class="antialiased selection:bg-[#FCA5FF] selection:text-black relative flex justify-center min-h-screen">

    {{-- Efek Glow Pink di tengah layar --}}
    <div class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

    {{-- CONTAINER MOBILE UTAMA (Konsisten dengan Welcome) --}}
    <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl shadow-2xl px-6 py-10">
        
        <div class="animate-slide-up w-full">
            <div class="text-center mb-10">
                <x-app-logo class="w-20 h-20 rounded-[2rem] mx-auto mb-5" />
                <h1 class="text-3xl font-bold text-white tracking-tight">Bendaharaku</h1>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-2 font-bold">Sign in to continue</p>
            </div>

            @if (session('status'))
                <div class="mb-4 text-sm font-medium text-green-400 text-center">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-[1.5rem] p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner">
                    @error('email') <span class="text-[10px] text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-[1.5rem] p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner">
                    @error('password') <span class="text-[10px] text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between px-1">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-[#1A1A1A] border-[#333] text-[#FCA5FF] focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        <span class="text-[11px] font-bold text-gray-500 group-hover:text-gray-300 transition-colors">Ingat Saya</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-[#FCA5FF] hover:text-white transition-colors">Lupa Password?</a>
                    @endif
                </div>

                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-[1.5rem] shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform mt-4">
                    Masuk
                </button>
            </form>

            {{-- DIVIDER ATAU --}}
            <div class="relative flex items-center justify-center mt-8 mb-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[#333]"></div>
                </div>
                <div class="relative flex justify-center text-[9px] uppercase font-bold tracking-widest">
                    <span class="bg-[#121212] px-4 text-gray-500">Atau</span>
                </div>
            </div>

            {{-- TOMBOL LOGIN GOOGLE --}}
            <a href="{{ route('google.login') }}" class="w-full flex items-center justify-center gap-3 bg-[#1A1A1A] text-white border border-[#333] font-bold text-xs uppercase tracking-widest py-4 rounded-[1.5rem] active:scale-95 transition-all hover:border-[#FCA5FF] hover:text-white shadow-sm">
                <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="w-5 h-5">
                Lanjut dengan Google
            </a>

            @if (Route::has('register'))
                <p class="text-center text-[11px] font-bold text-gray-500 mt-10">
                    Belum punya akun? <a href="{{ route('register') }}" class="text-[#FCA5FF] hover:text-white transition-colors">Daftar di sini</a>
                </p>
            @endif
        </div>
        
    </div>
</body>
</html>