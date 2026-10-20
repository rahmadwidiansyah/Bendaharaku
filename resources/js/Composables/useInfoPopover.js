import { nextTick, onBeforeUnmount, ref, useId } from 'vue'

/**
 * Shared behaviour for click-to-open info popovers.
 *
 * The panel is teleported to <body>, so positioning is calculated from the
 * trigger rect and clamped to the viewport. Wrappers only own visual tokens.
 */
export function useInfoPopover() {
    const isOpen = ref(false)
    const rootRef = ref(null)
    const panelRef = ref(null)
    const panelStyle = ref({})

    const uid = useId()
    const panelId = `info-popover-${uid}`
    const titleId = `info-popover-title-${uid}`

    let viewportBound = false
    let documentBound = false

    function triggerElement() {
        return rootRef.value?.querySelector('[data-popover-trigger]') || null
    }

    function updatePosition() {
        const trigger = triggerElement()
        const panel = panelRef.value
        if (!trigger || !panel) return

        const margin = 8
        const rect = trigger.getBoundingClientRect()
        const width = Math.min(288, window.innerWidth - margin * 2)
        const height = panel.offsetHeight || 0

        let left = rect.right - width
        left = Math.min(Math.max(margin, left), window.innerWidth - width - margin)

        let top = rect.bottom + margin
        if (top + height > window.innerHeight - margin) {
            const above = rect.top - height - margin
            top = above >= margin ? above : Math.max(margin, window.innerHeight - height - margin)
        }

        panelStyle.value = {
            left: `${Math.round(left)}px`,
            top: `${Math.round(top)}px`,
            width: `${Math.round(width)}px`,
        }
    }

    function bindViewport() {
        if (viewportBound) return
        window.addEventListener('resize', updatePosition)
        window.addEventListener('scroll', updatePosition, true)
        viewportBound = true
    }

    function unbindViewport() {
        if (!viewportBound) return
        window.removeEventListener('resize', updatePosition)
        window.removeEventListener('scroll', updatePosition, true)
        viewportBound = false
    }

    function bindDocument() {
        if (documentBound) return
        document.addEventListener('pointerdown', onPointerDown, true)
        document.addEventListener('keydown', onKeydown)
        documentBound = true
    }

    function unbindDocument() {
        if (!documentBound) return
        document.removeEventListener('pointerdown', onPointerDown, true)
        document.removeEventListener('keydown', onKeydown)
        documentBound = false
    }

    async function open() {
        if (isOpen.value) return
        isOpen.value = true
        bindDocument()
        bindViewport()
        await nextTick()
        updatePosition()
        panelRef.value?.focus()
    }

    function close(restoreFocus = true) {
        if (!isOpen.value) return
        isOpen.value = false
        unbindDocument()
        unbindViewport()
        if (restoreFocus) {
            nextTick(() => triggerElement()?.focus())
        }
    }

    function toggle() {
        isOpen.value ? close() : open()
    }

    function onPointerDown(event) {
        if (!isOpen.value) return
        const target = event.target
        if (rootRef.value?.contains(target) || panelRef.value?.contains(target)) return
        close(false)
    }

    function onKeydown(event) {
        if (event.key !== 'Escape' || !isOpen.value) return
        event.stopPropagation()
        close()
    }

    onBeforeUnmount(() => {
        unbindDocument()
        unbindViewport()
    })

    return {
        isOpen,
        rootRef,
        panelRef,
        panelId,
        titleId,
        panelStyle,
        open,
        close,
        toggle,
    }
}
