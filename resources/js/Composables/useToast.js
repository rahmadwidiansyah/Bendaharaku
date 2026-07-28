import { ref } from 'vue'

const message = ref(null)
const toastType = ref('success')
const visible = ref(false)
let timer = null

export function useToast() {
  const showToast = (msg, type = 'success') => {
    if (timer) clearTimeout(timer)
    message.value = msg
    toastType.value = type
    visible.value = true
    timer = setTimeout(() => {
      visible.value = false
    }, 3000)
  }

  return { message, toastType, visible, showToast }
}
