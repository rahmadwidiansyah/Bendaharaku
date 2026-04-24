@props(['id' => 'icon', 'default' => '💳'])

<div class="shrink-0 flex flex-col justify-end">
    <label class="block text-sm font-medium text-gray-300 mb-2 ml-1 text-center">Ikon</label>
    
    {{-- Tombol Preview (DIPERBAIKI: Overflow Hidden & Image Styling) --}}
    <button type="button" onclick="toggleEmojiPicker_{{ $id }}()" id="{{ $id }}Preview" 
        class="w-[60px] h-[60px] bg-[#1A1A1A] border border-[#333] text-white rounded-2xl flex items-center justify-center text-3xl hover:border-[#FCA5FF] focus:border-[#FCA5FF] transition-all active:scale-95 shadow-inner overflow-hidden relative">
        
        @if(Str::contains($default, ['.png', '.jpg', '.jpeg', '.webp', '/']))
            <img src="{{ asset('storage/' . $default) }}" class="absolute inset-0 w-full h-full object-cover">
        @else
            <span>{{ $default }}</span>
        @endif
    </button>
    
    {{-- Input Hidden untuk Text Emoji --}}
    <input type="hidden" name="{{ $id }}" id="{{ $id }}Input" value="{{ $default }}">
    {{-- Input Hidden untuk File Upload --}}
    <input type="file" name="{{ $id }}_file" id="{{ $id }}File" accept="image/*" class="hidden" onchange="handleFileUpload_{{ $id }}(event)">
</div>

{{-- OVERLAY & MODAL --}}
<div id="emojiOverlay_{{ $id }}" class="fixed inset-0 z-[9998] hidden bg-black/80 backdrop-blur-sm" style="align-items: center; justify-content: center;">
    <div class="absolute inset-0" onclick="toggleEmojiPicker_{{ $id }}()"></div>

    <div id="emojiModal_{{ $id }}" class="relative z-10 w-[90%] max-w-sm bg-[#121212] rounded-3xl border border-[#333] shadow-[0_20px_50px_rgba(0,0,0,0.7)] flex flex-col overflow-hidden transition-all duration-300 ease-out transform scale-95 opacity-0 pointer-events-none" style="max-height: 70vh;">
        
        <div class="px-5 py-4 border-b border-[#262626] flex justify-between items-center bg-[#1A1A1A]">
            <h3 class="text-sm font-semibold text-white">Pilih Ikon</h3>
            <button type="button" onclick="toggleEmojiPicker_{{ $id }}()" class="text-gray-400 hover:text-white active:scale-90 transition-transform p-1">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        
        <div class="p-5 overflow-y-auto no-scrollbar flex-1 bg-[#121212]" id="emojiScrollArea_{{ $id }}">
            <div class="grid grid-cols-6 gap-3" id="emojiGrid_{{ $id }}"></div>
        </div>

        <div class="p-2 border-t border-[#262626] bg-[#1A1A1A] flex justify-around items-center" id="emojiCategories_{{ $id }}"></div>
    </div>
</div>

<script>
    if (typeof window.emojiData === 'undefined') {
        window.emojiData = [
            { id: 'finance', icon: '💰', list: ['💳','💰','🏦','💵','🪙','🏧','💸','💎','📈','🧾','🧧'] },
            { id: 'smileys', icon: '😀', list: ['😀','😂','🥰','😎','🤔','😭','😡','🤯','😴','🤮','🤡','👻','👽','🤖'] },
            { id: 'food', icon: '🍔', list: ['🍎','🍔','🍕','☕','🍺','🍦','🥩','🍙','🍱','🍰'] },
            { id: 'transport', icon: '🚗', list: ['🚗','🛵','🚲','✈️','🚀','🛳️','⛽','🚆','🚜'] },
            { id: 'places', icon: '🏠', list: ['🏠','🏢','🏥','🏪','🏫','🏖️','⛺','🎡','⛩️'] },
            { id: 'objects', icon: '💡', list: ['📱','💻','🛍️','🎁','🔑','🔓','💊','🛒','📸','🎮','🔧'] },
            { 
                id: 'images', 
                icon: '🖼️', 
                type: 'custom',
                defaults: [
                    'https://pustaka.bca.co.id/public-assets/logo-bca.svg',
                    'defaults/mandiri.png',
                    'defaults/gopay.png',
                    'defaults/dana.png',
                    'defaults/ovo.png',
                    'defaults/shopeepay.png',
                    'defaults/cash.png'
                ]
            } 
        ];
    }

    window['activeCategory_{{ $id }}'] = 'finance'; 

    window['toggleEmojiPicker_{{ $id }}'] = function() {
        const overlay = document.getElementById('emojiOverlay_{{ $id }}');
        const modal = document.getElementById('emojiModal_{{ $id }}');
        
        if (overlay.classList.contains('hidden')) {
            overlay.classList.remove('hidden'); overlay.style.display = 'flex'; 
            window['renderCategoryTabs_{{ $id }}'](); window['renderEmojis_{{ $id }}'](window['activeCategory_{{ $id }}']);
            setTimeout(() => { modal.classList.remove('scale-95', 'opacity-0', 'pointer-events-none'); modal.classList.add('scale-100', 'opacity-100'); }, 10);
        } else {
            modal.classList.remove('scale-100', 'opacity-100'); modal.classList.add('scale-95', 'opacity-0', 'pointer-events-none');
            setTimeout(() => { overlay.classList.add('hidden'); overlay.style.display = ''; }, 300);
        }
    };

    window['renderCategoryTabs_{{ $id }}'] = function() {
        const catContainer = document.getElementById('emojiCategories_{{ $id }}');
        if (catContainer.children.length > 0) return; 

        window.emojiData.forEach(cat => {
            const btn = document.createElement('button');
            btn.type = 'button'; btn.id = `catBtn_{{ $id }}_${cat.id}`;
            btn.className = "p-2 rounded-xl text-xl transition-all duration-200 opacity-50 hover:opacity-100 active:scale-90 flex items-center justify-center";
            if (cat.id === window['activeCategory_{{ $id }}']) { btn.classList.remove('opacity-50'); btn.classList.add('bg-[#262626]', 'opacity-100', 'shadow-inner'); }
            btn.innerText = cat.icon;
            btn.onclick = (e) => { e.stopPropagation(); window['renderEmojis_{{ $id }}'](cat.id); };
            catContainer.appendChild(btn);
        });
    };

    window['renderEmojis_{{ $id }}'] = function(categoryId) {
        const grid = document.getElementById('emojiGrid_{{ $id }}'); grid.innerHTML = ''; 
        
        window.emojiData.forEach(cat => {
            const btn = document.getElementById(`catBtn_{{ $id }}_${cat.id}`);
            if(btn) {
                if (cat.id === categoryId) { btn.classList.remove('opacity-50'); btn.classList.add('bg-[#262626]', 'opacity-100', 'shadow-inner'); } 
                else { btn.classList.remove('bg-[#262626]', 'opacity-100', 'shadow-inner'); btn.classList.add('opacity-50'); }
            }
        });

        window['activeCategory_{{ $id }}'] = categoryId;
        const categoryData = window.emojiData.find(c => c.id === categoryId);
        
        if (categoryData) {
            if(categoryData.type === 'custom') {
                grid.className = "grid grid-cols-3 gap-3"; 
                
                const uploadBtn = document.createElement('button');
                uploadBtn.type = 'button';
                uploadBtn.className = "aspect-square bg-[#1A1A1A] border-2 border-dashed border-[#444] hover:border-[#FCA5FF] rounded-2xl flex flex-col items-center justify-center text-gray-400 hover:text-[#FCA5FF] transition-all active:scale-95";
                uploadBtn.innerHTML = `<svg class="w-8 h-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg><span class="text-[9px] font-bold uppercase tracking-widest">Upload</span>`;
                uploadBtn.onclick = (e) => { e.stopPropagation(); document.getElementById('{{ $id }}File').click(); };
                grid.appendChild(uploadBtn);

                categoryData.defaults.forEach(path => {
                    const imgBtn = document.createElement('button');
                    imgBtn.type = 'button';
                    imgBtn.className = "aspect-square bg-[#1A1A1A] border border-[#333] rounded-2xl overflow-hidden hover:border-[#FCA5FF] transition-all active:scale-95 p-1";
                    const fullUrl = `/storage/${path}`;
                    imgBtn.innerHTML = `<img src="${fullUrl}" class="w-full h-full object-cover rounded-xl">`;
                    imgBtn.onclick = (e) => {
                        e.stopPropagation();
                        document.getElementById('{{ $id }}Input').value = path; 
                        document.getElementById('{{ $id }}File').value = ''; 
                        document.getElementById('{{ $id }}Preview').innerHTML = `<img src="${fullUrl}" class="absolute inset-0 w-full h-full object-cover">`;
                        window['toggleEmojiPicker_{{ $id }}'](); 
                    };
                    grid.appendChild(imgBtn);
                });
            } else {
                grid.className = "grid grid-cols-6 gap-3";
                const fragment = document.createDocumentFragment();
                categoryData.list.forEach(emoji => {
                    const btn = document.createElement('button'); btn.type = 'button';
                    btn.className = "text-2xl p-2 hover:bg-[#262626] rounded-xl transition-all active:scale-75 flex items-center justify-center transform hover:scale-110 duration-200";
                    btn.innerText = emoji;
                    btn.onclick = (e) => {
                        e.stopPropagation();
                        document.getElementById('{{ $id }}Input').value = emoji;
                        document.getElementById('{{ $id }}File').value = '';
                        document.getElementById('{{ $id }}Preview').innerHTML = `<span>${emoji}</span>`;
                        window['toggleEmojiPicker_{{ $id }}']();
                    };
                    fragment.appendChild(btn);
                });
                grid.appendChild(fragment);
            }
            const scrollArea = document.getElementById('emojiScrollArea_{{ $id }}'); if(scrollArea) scrollArea.scrollTop = 0;
        }
    };

    window['handleFileUpload_{{ $id }}'] = function(event) {
        const file = event.target.files[0];
        if(file) {
            const tempUrl = URL.createObjectURL(file);
            document.getElementById('{{ $id }}Input').value = ''; 
            document.getElementById('{{ $id }}Preview').innerHTML = `<img src="${tempUrl}" class="absolute inset-0 w-full h-full object-cover">`;
            window['toggleEmojiPicker_{{ $id }}'](); 
        }
    };
</script>
<style>.no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }</style>