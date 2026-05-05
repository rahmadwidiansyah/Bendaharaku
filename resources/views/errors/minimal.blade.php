<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700,800,900&display=swap" rel="stylesheet" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: linear-gradient(to bottom right, #111827, #1f2937);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .bg-pattern {
            background-color: #1f2937;
            background-image: radial-gradient(rgba(252, 165, 255, 0.05) 1px, transparent 1px);
            background-size: 32px 32px;
        }
    </style>
</head>
<body class="bg-pattern font-sans antialiased text-white min-h-screen flex items-center justify-center overflow-hidden relative selection:bg-purple-500/10 selection:text-black">
    
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-purple-500/10 rounded-full mix-blend-screen filter blur-[100px] opacity-[0.07] animate-pulse"></div>
    <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-purple-500/10 rounded-full mix-blend-screen filter blur-[120px] opacity-[0.07] animate-pulse" style="animation-delay: 2s;"></div>

    <main class="relative z-10 p-8 w-full max-w-2xl text-center">
        <div class="animate-float mb-8">
            <h1 class="text-[10rem] md:text-[12rem] font-black leading-none tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-purple-500 via-purple-500 to-indigo-600 drop-shadow-2xl">
                @yield('code')
            </h1>
        </div>
        
        <div class="glass-panel p-8 md:p-12 rounded-3xl transform transition-all duration-500 hover:scale-[1.02] hover:shadow-2xl">
            <div class="inline-flex items-center justify-center px-4 py-1.5 mb-6 text-sm font-semibold tracking-widest text-purple-500 uppercase bg-purple-500/10 border border-purple-500/20 rounded-full">
                <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Error @yield('code')
            </div>
            
            <h2 class="text-3xl md:text-4xl font-bold mb-4 text-white tracking-tight">
                Oops! Terjadi Kesalahan.
            </h2>
            
            <p class="text-lg md:text-xl text-gray-400 mb-10 max-w-lg mx-auto leading-relaxed">
                @yield('message')
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <button onclick="window.history.back()" class="w-full sm:w-auto px-8 py-3.5 text-sm font-bold tracking-wider text-white uppercase transition-all duration-300 bg-gradient-to-br from-purple-800 to-purple-500 rounded-xl hover:bg-white hover:shadow-lg hover:shadow-purple-500/20 active:scale-95">
                    Kembali
                </button>
                <a href="{{ url('/') }}" class="w-full sm:w-auto px-8 py-3.5 text-sm font-bold tracking-wider text-white uppercase transition-all duration-300 bg-gradient-to-br from-purple-500 to-purple-800 border-white/10 rounded-xl hover:bg-white/5 hover:border-white/10 active:scale-95">
                    Halaman Utama
                </a>
            </div>
        </div>
    </main>
</body>
</html>
