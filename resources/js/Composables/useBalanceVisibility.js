import { ref, watch } from 'vue';

const isBalanceVisible = ref(localStorage.getItem('isBalanceVisible') !== 'false');

watch(isBalanceVisible, (val) => {
    localStorage.setItem('isBalanceVisible', val);
});

export function useBalanceVisibility() {
    const toggleVisibility = () => {
        isBalanceVisible.value = !isBalanceVisible.value;
    };

    return {
        isBalanceVisible,
        toggleVisibility
    };
}
