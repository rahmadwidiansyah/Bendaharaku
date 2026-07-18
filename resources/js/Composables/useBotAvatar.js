/**
 * useBotAvatar.js
 *
 * Composable untuk avatar dengan fallback initials.
 * Dipakai di: ChatHeader, ChatMessage (bot + user), TypingIndicator, ChatEmptyState.
 *
 * Usage:
 *   const { avatarFailed, initials, onAvatarError } = useBotAvatar(name)
 *   const { avatarFailed, initials, onAvatarError } = useBotAvatar(() => props.botName)
 */

import { ref, computed, isRef, toRef } from 'vue'

/**
 * @param {string | Ref<string> | (() => string)} name - Nama yang dijadikan initials
 * @returns {{ avatarFailed: Ref<boolean>, initials: ComputedRef<string>, onAvatarError: () => void }}
 */
export function useBotAvatar(name) {
    const avatarFailed = ref(false)

    // Normalise berbagai tipe input ke computed
    const nameRef = typeof name === 'function'
        ? computed(name)
        : isRef(name)
            ? name
            : toRef(typeof name === 'string' ? { value: name } : name, 'value')

    const initials = computed(() => {
        const raw = nameRef.value ?? ''
        return raw.trim().split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase() ?? '').join('')
    })

    function onAvatarError() {
        avatarFailed.value = true
    }

    return {
        avatarFailed,
        initials,
        onAvatarError,
    }
}
