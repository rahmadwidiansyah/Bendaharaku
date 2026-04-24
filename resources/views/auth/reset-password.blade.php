<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Bendaharaku</title>
    
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
            <div class="text-center mb-10">
                <x-app-logo class="w-20 h-20 rounded-[2rem] mx-auto mb-5" />
                <h1 class="text-3xl font-bold text-white tracking-tight">Reset Password</h1>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-2 font-bold">Buat sandi baru Anda</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-[1.5rem] p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner">
                    @error('email') <span class="text-[10px] text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Password Baru</label>
                    <input type="password" name="password" required autocomplete="new-password"
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-[1.5rem] p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner">
                    @error('password') <span class="text-[10px] text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full bg-[#1A1A1A] border border-[#333] text-white rounded-[1.5rem] p-4 text-sm focus:border-[#FCA5FF] focus:outline-none focus:ring-1 focus:ring-[#FCA5FF] transition-all shadow-inner">
                    @error('password_confirmation') <span class="text-[10px] text-red-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full bg-[#FCA5FF] text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-[1.5rem] shadow-[0_0_20px_rgba(252,165,255,0.2)] active:scale-95 transition-transform mt-6">
                    Simpan Password
                </button>
            </form>
        </div>
        
    </div>
</body>
</html>