import { ref } from 'vue';

const saved = localStorage.getItem('layout_preference');

// Mode otomatis (tanpa preferensi tersimpan): layout mengikuti lebar
// window secara live — termasuk rotasi/resize perangkat. Setelah user
// men-toggle secara eksplisit, preferensi terkunci dan tersimpan.
let autoMode = saved === null;

const isDesktopLayout = ref(
    autoMode
        ? window.innerWidth >= 1024
        : saved === 'desktop'
);

if (autoMode) {
    window.addEventListener('resize', () => {
        if (!autoMode) return;
        isDesktopLayout.value = window.innerWidth >= 1024;
    });
}

export function useLayoutPreference() {
    const toggleLayout = () => {
        isDesktopLayout.value = !isDesktopLayout.value;
        autoMode = false;
        localStorage.setItem('layout_preference', isDesktopLayout.value ? 'desktop' : 'mobile');
    };

    return {
        isDesktopLayout,
        toggleLayout
    };
}
