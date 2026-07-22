import { onMounted, onUnmounted } from 'vue'

export function useWizardNavigation({ formStep, resetToStep, onBackFromFirstStep }) {
  function onPopState() {
    if (formStep.value > 1) {
      resetToStep(formStep.value - 1)
      window.history.pushState(null, '', window.location.href)
    }
  }

  function goBack() {
    if (formStep.value > 1) {
      resetToStep(formStep.value - 1)
    } else if (onBackFromFirstStep) {
      onBackFromFirstStep()
    }
  }

  function pushStepState() {
    window.history.pushState(null, '', window.location.href)
  }

  onMounted(() => {
    if (formStep.value > 1) {
      window.history.pushState(null, '', window.location.href)
    }
    window.addEventListener('popstate', onPopState)
  })

  onUnmounted(() => {
    window.removeEventListener('popstate', onPopState)
  })

  return { goBack, pushStepState }
}
