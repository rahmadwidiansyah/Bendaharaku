<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email - Bendaharaku</title>
    
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

    {{-- Efek Glow Pink di tengah layar --}}
    <div class="fixed top-[20%] left-[50%] -translate-x-1/2 w-[300px] h-[300px] bg-[#FCA5FF] blur-[120px] opacity-[0.15] rounded-full pointer-events-none z-0"></div>

    {{-- CONTAINER MOBILE UTAMA --}}
    <div class="w-full max-w-md relative z-10 flex flex-col justify-center min-h-screen border-x border-[#262626]/50 bg-[#121212]/80 backdrop-blur-xl shadow-2xl px-6 py-10">
        
        <div class="animate-slide-up w-full">
            <div class="text-center mb-8">
                <x-app-logo class="w-20 h-20 rounded-[2rem] mx-auto mb-5" />
                <h1 class="text-3xl font-bold text-white tracking-tight">Cek Email Anda</h1>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-2 font-bold">Verifikasi untuk melanjutkan</p>
            </div>

            <p class="text-[11px] text-gray-400 text-center mb-6 leading-relaxed px-2">
                Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda memverifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan? Jika tidak menerimanya, kami akan mengirim ulang.
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-6 p-4 rounded-2xl bg-[#1A1A1A] border border-green-500/30 text-[11px] font-bold text-green-400 text-center shadow-sm">
                    Link verifikasi baru telah dikirim ke alamat email yang Anda daftarkan.
                </div>
            @endif

            <div class="space-y-4 mt-8">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-[1.5rem] shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform">
                        Kirim Ulang Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="text-center mt-6">
                    @csrf
                    <button type="submit" class="text-[11px] font-bold text-gray-500 hover:text-white transition-colors uppercase tracking-widest">
                        Keluar Akun
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</body>
</html>