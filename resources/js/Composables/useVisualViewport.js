/**
 * useVisualViewport.js
 *
 * Reactive wrapper around window.visualViewport API.
 *
 * Provides:
 *   - height:      Current visual viewport height (px), updates on resize
 *   - width:       Current visual viewport width (px), updates on resize
 *   - offsetTop:   Distance from top of layout viewport to visual viewport
 *   - offsetLeft:  Distance from left of layout viewport to visual viewport
 *   - scale:       Current pinch scale
 *   - isKeyboardOpen: True when viewport is shorter than window.innerHeight
 *
 * On Android, when the virtual keyboard opens:
 *   - visualViewport.height shrinks (unlike 100dvh which may not)
 *   - visualViewport.offsetTop may change
 *
 * Fallback: if visualViewport is not supported, uses window.innerHeight.
 */

import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useVisualViewport() {
    const height      = ref(0)
    const width       = ref(0)
    const offsetTop   = ref(0)
    const offsetLeft  = ref(0)
    const scale       = ref(1)
    const isKeyboardOpen = ref(false)

    function update() {
        if (typeof window === 'undefined') return

        const vv = window.visualViewport
        if (vv) {
            height.value     = vv.height
            width.value      = vv.width
            offsetTop.value  = vv.offsetTop
            offsetLeft.value = vv.offsetLeft
            scale.value      = vv.scale
        } else {
            // Fallback for browsers without visualViewport
            height.value = window.innerHeight
            width.value  = window.innerWidth
        }

        // Detect keyboard: visual viewport significantly shorter than layout viewport
        const layoutHeight = window.innerHeight
        isKeyboardOpen.value = layoutHeight - height.value > 150
    }

    let rafId = null

    function onResize() {
        // Throttle via requestAnimationFrame for smooth updates
        if (rafId) cancelAnimationFrame(rafId)
        rafId = requestAnimationFrame(update)
    }

    onMounted(() => {
        update()
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', onResize, { passive: true })
            window.visualViewport.addEventListener('scroll', onResize, { passive: true })
        }
        // Also listen to orientationchange for screen rotation
        window.addEventListener('orientationchange', onResize, { passive: true })
    })

    onBeforeUnmount(() => {
        if (rafId) cancelAnimationFrame(rafId)
        if (window.visualViewport) {
            window.visualViewport.removeEventListener('resize', onResize)
            window.visualViewport.removeEventListener('scroll', onResize)
        }
        window.removeEventListener('orientationchange', onResize)
    })

    return {
        height,
        width,
        offsetTop,
        offsetLeft,
        scale,
        isKeyboardOpen,
    }
}
