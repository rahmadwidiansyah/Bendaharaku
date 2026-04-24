<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-black">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Bendaharaku V4</title>
        
        {{-- Resources --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        <script src="https://cdn.tailwindcss.com"></script>

        <style>
            ::-webkit-scrollbar { width: 0px; background: transparent; }
            .no-scrollbar::-webkit-scrollbar { display: none; }
            .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

            .livewire-progress-bar {
                height: 3px !important;
                background-color: #FCA5FF !important;
                box-shadow: 0 0 10px #FCA5FF;
                z-index: 9999;
            }

            @keyframes fadeInPage {
                0% { opacity: 0; transform: translateY(15px); }
                100% { opacity: 1; transform: translateY(0); }
            }

            .animate-page {
                animation: fadeInPage 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                will-change: opacity, transform;
            }

            /* Mengurangi efek jumping pada Safari/iOS */
            nav {
                -webkit-backface-visibility: hidden;
                backface-visibility: hidden;
                transform: translateZ(0);
            }
       </style>
       @livewireStyles
    </head>
    
    <body class="font-sans antialiased bg-black text-white selection:bg-[#FCA5FF] selection:text-black">
        <div class="w-full max-w-md md:max-w-full mx-auto bg-gray-800 min-h-screen flex flex-col shadow-2xl border-x border-[#1e1e1e] relative">
        
        <!-- <div class="w-full max-w-md mx-auto bg-[#121212] min-h-screen flex flex-col shadow-2xl border-x border-[#1e1e1e] relative"> -->
            
            <main id="main-content" class="flex-1 animate-page pb-24"> 
                {{ $slot }} 
            </main>
            @stack('modals')
            <x-bottom-nav />
        </div>

        @livewireScripts

        <script>
            // 1. BERSIHKAN RAM SEBELUM PINDAH
            document.addEventListener('livewire:navigate', () => {
                if (window.myCharts) {
                    Object.keys(window.myCharts).forEach(key => {
                        if(window.myCharts[key]) {
                            try { window.myCharts[key].destroy(); delete window.myCharts[key]; } catch(e) {}
                        }
                    });
                }
            });

            // 2. SETELAH PINDAH: RENDER & SCROLL
            document.addEventListener('livewire:navigated', () => {
                window.scrollTo({ top: 0, behavior: 'instant' });

                const el = document.getElementById('main-content');
                if (el) {
                    // Restart animasi
                    el.classList.remove('animate-page');
                    void el.offsetWidth; // Trigger reflow
                    el.classList.add('animate-page');

                    // SOLUSI KUTUKAN CSS: Gunakan 'animationend' alih-alih setTimeout
                    // Ini memastikan class dicopot TEPAT setelah animasi selesai, berapapun durasinya di CSS.
                    el.addEventListener('animationend', function handler() {
                        el.classList.remove('animate-page');
                        el.removeEventListener('animationend', handler); // Hapus listener agar tidak menumpuk
                    });
                }

                // Jalankan fungsi grafik secara aman
                try { if (typeof initMainGraph === 'function') initMainGraph(); } catch (e) {}
                try { if (typeof switchChart === 'function') switchChart('expense'); } catch (e) {}
            });
        </script>
    </body>
</html>