<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bendaharaku - Kelola Keuangan Pintar</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700,800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #121212; color: #ffffff; overflow-x: hidden; }
        
        /* Animasi Keren */
        @keyframes fade-in-up { 
            0% { opacity: 0; transform: translateY(20px); } 
            100% { opacity: 1; transform: translateY(0); } 
        }
        @keyframes float { 
            0% { transform: translateY(0px); } 
            50% { transform: translateY(-10px); } 
            100% { transform: translateY(0px); } 
        }
        
        .animate-fade-in-up { animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .animate-float { animation: float 6s ease-in-out infinite; }
        
        /* Sembunyikan scrollbar tapi tetep bisa scroll */
        ::-webkit-scrollbar { width: 0px; background: transparent; }
    </style>
</head>
<body class="antialiased selection:bg-[#FCA5FF] selection:text-black relative flex justify-center min-h-screen">

    {{-- Efek Glow Pink ditaruh di tengah layar Mobile --}}
    <div class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

    {{-- CONTAINER MOBILE UTAMA --}}
    <div class="w-full max-w-md relative z-10 flex flex-col min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl shadow-2xl">
        
        {{-- NAVBAR MOBILE --}}
        <nav class="w-full p-5 flex justify-between items-center relative z-50">
            <div class="flex items-center gap-2">
                <x-app-logo class="w-8 h-8 rounded-lg" />
                <span class="font-bold text-lg tracking-tight text-white">Bendaharaku</span>
            </div>
            <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-[10px] font-bold bg-[#1A1A1A] border border-[#333] text-[#FCA5FF] px-4 py-2 rounded-xl hover:bg-[#262626] transition-all uppercase tracking-widest">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-[10px] font-bold text-gray-400 hover:text-white transition-colors uppercase tracking-widest">Login</a>
                    @endauth
                @endif
            </div>
        </nav>

        {{-- MAIN CONTENT MOBILE --}}
        <main class="flex-1 flex flex-col items-center px-5 pt-8 pb-10">
            
            {{-- HERO SECTION --}}
            <div class="text-center w-full animate-fade-in-up">
                <span class="inline-block py-1.5 px-4 rounded-full bg-[#1A1A1A] border border-[#333] text-[#FCA5FF] text-[9px] font-bold uppercase tracking-widest mb-6 shadow-sm">
                    Rilis Terbaru v4.0
                </span>
                
                <h1 class="text-4xl font-black tracking-tighter mb-4 leading-[1.1]">
                    Kendali Finansial <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#FCA5FF] to-[#A78BFA] drop-shadow-lg">Di Ujung Jari.</span>
                </h1>
                
                <p class="text-gray-400 text-xs mb-8 leading-relaxed font-medium px-2">
                    Catat pemasukan harian, pantau saldo dompet, hingga lacak hutang teman. Semua dalam balutan dark-mode premium yang pas di kantong.
                </p>
                
                {{-- TOMBOL AKSI --}}
                <div class="flex flex-col gap-3 w-full">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-[11px] uppercase tracking-widest py-4 rounded-[1.5rem] shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform">
                                Buka Aplikasi &rarr;
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-[11px] uppercase tracking-widest py-4 rounded-[1.5rem] shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform text-center">
                                Buat Akun Baru
                            </a>
                            <a href="{{ route('login') }}" class="w-full bg-[#1A1A1A] border border-[#333] text-white font-bold text-[11px] uppercase tracking-widest py-4 rounded-[1.5rem] hover:border-[#FCA5FF] active:scale-95 transition-all text-center">
                                Sudah Punya Akun
                            </a>
                        @endauth
                    @endif
                </div>
            </div>

            {{-- FITUR CARDS (VERTIKAL STACK) --}}
            <div class="flex flex-col gap-4 mt-12 w-full">
                
                <div class="bg-gradient-to-br from-[#1A1A1A] to-[#121212] border border-[#262626] rounded-[1.5rem] p-5 flex items-start gap-4 animate-fade-in-up delay-100 animate-float" style="animation-delay: 0s;">
                    <div class="w-12 h-12 shrink-0 bg-[#262626] rounded-[1rem] flex items-center justify-center text-xl border border-[#333] shadow-inner">📊</div>
                    <div>
                        <h3 class="text-sm font-bold text-white mb-1 tracking-tight">Grafik Pintar</h3>
                        <p class="text-[11px] text-gray-500 leading-relaxed">Pantau arus kas dengan diagram interaktif bulanan.</p>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-[#1A1A1A] to-[#121212] border border-[#262626] rounded-[1.5rem] p-5 flex items-start gap-4 animate-fade-in-up delay-200 animate-float" style="animation-delay: 1s;">
                    <div class="w-12 h-12 shrink-0 bg-[#262626] rounded-[1rem] flex items-center justify-center text-xl border border-[#333] shadow-inner">💳</div>
                    <div>
                        <h3 class="text-sm font-bold text-white mb-1 tracking-tight">Multi Dompet</h3>
                        <p class="text-[11px] text-gray-500 leading-relaxed">Pisahkan uang di BCA, Gopay, atau Uang Tunai.</p>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-[#1A1A1A] to-[#121212] border border-[#262626] rounded-[1.5rem] p-5 flex items-start gap-4 animate-fade-in-up delay-300 animate-float" style="animation-delay: 2s;">
                    <div class="w-12 h-12 shrink-0 bg-[#262626] rounded-[1rem] flex items-center justify-center text-xl border border-[#333] shadow-inner">🤝</div>
                    <div>
                        <h3 class="text-sm font-bold text-white mb-1 tracking-tight">Lacak Hutang</h3>
                        <p class="text-[11px] text-gray-500 leading-relaxed">Catat siapa saja yang ngutang beserta umur hutangnya.</p>
                    </div>
                </div>

            </div>

            <footer class="mt-12 text-center animate-fade-in-up delay-300">
                <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">
                    &copy; {{ date('Y') }} Bendaharaku V4.<br>Dibuat untuk Manajemen Keuangan.
                </p>
            </footer>

        </main>
    </div>

</body>
</html>