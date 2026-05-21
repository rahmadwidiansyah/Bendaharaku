import { ref, watch } from 'vue';

const isDesktopLayout = ref(localStorage.getItem('layout_preference') !== 'mobile');

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
