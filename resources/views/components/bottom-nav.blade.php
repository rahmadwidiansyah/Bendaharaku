<nav class="fixed bottom-3 rounded-xl left-1/2 -translate-x-1/2 w-full max-w-md bg-gray-900/90 backdrop-blur-xl border-t border-white/5 pb-safe z-50">
    <div class="flex justify-around items-center pt-3 pb-4 px-2">
        
        {{-- Home --}}
        <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col items-center gap-1 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'text-[#FCA5FF] scale-105' : 'text-gray-500 hover:text-gray-300' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span class="text-[10px] font-bold tracking-wider uppercase">Home</span>
        </a>

        {{-- Grafik --}}
        <a href="{{ route('analytics.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition-all duration-200 {{ request()->routeIs('analytics.*') ? 'text-[#FCA5FF] scale-105' : 'text-gray-500 hover:text-gray-300' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 003.055 11H11V3.055zM20.945 13H13v7.945a9.003 9.003 0 007.945-7.945z"></path>
            </svg>
            <span class="text-[10px] font-bold tracking-wider uppercase">Grafik</span>
        </a>

        {{-- Histori --}}
        <a href="{{ route('transactions.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition-all duration-200 {{ request()->routeIs('transactions.*') ? 'text-[#FCA5FF] scale-105' : 'text-gray-500 hover:text-gray-300' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <span class="text-[10px] font-bold tracking-wider uppercase">Histori</span>
        </a>

        {{-- Kategori --}}
        <a href="{{ route('categories.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition-all duration-200 {{ request()->routeIs('categories.*') ? 'text-[#FCA5FF] scale-105' : 'text-gray-500 hover:text-gray-300' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span class="text-[10px] font-bold tracking-wider uppercase">Kategori</span>
        </a>
        
    </div>
</nav>