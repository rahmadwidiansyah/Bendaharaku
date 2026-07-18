import { ref, watch } from 'vue';

const saved = localStorage.getItem('layout_preference');

const isDesktopLayout = ref(
    saved === null
        ? window.innerWidth >= 1024
        : saved === 'desktop'
);

watch(isDesktopLayout, (newValue) => {
    localStorage.setItem('layout_preference', newValue ? 'desktop' : 'mobile');
});

export function useLayoutPreference() {
    const toggleLayout = () => {
        isDesktopLayout.value = !isDesktopLayout.value;
    };
    
    return {
        isDesktopLayout,
        toggleLayout
    };
}
