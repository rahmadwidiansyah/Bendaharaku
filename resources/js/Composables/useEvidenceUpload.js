import { ref, computed, reactive } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

export const UploadState = {
    PENDING: 'PENDING',
    UPLOADING: 'UPLOADING',
    UPLOADED: 'UPLOADED',
    PROCESSING: 'PROCESSING',
    READY: 'READY',
    FAILED: 'FAILED',
}

export function useEvidenceUpload() {
    // i18n fallback — jika diluar setup, t akan fallback ke key
    let t = (k) => k
    try { t = useI18n().t } catch (_) {}

    const state = ref(UploadState.PENDING)
    const progress = ref(0)
    const error = ref(null)
    const evidence = ref(null)
    const localPreviewUrl = ref(null)
    const uploadedFile = ref(null)

    const isUploading = computed(() => state.value === UploadState.UPLOADING)
    const isPending = computed(() => state.value === UploadState.PENDING)
    const isUploaded = computed(() => state.value === UploadState.UPLOADED)
    const isProcessing = computed(() => state.value === UploadState.PROCESSING)
    const isReady = computed(() => state.value === UploadState.READY)
    const isFailed = computed(() => state.value === UploadState.FAILED)
    const isDone = computed(() => state.value === UploadState.READY || state.value === UploadState.FAILED)

    function setFile(file) {
        if (localPreviewUrl.value) {
            URL.revokeObjectURL(localPreviewUrl.value)
        }
        uploadedFile.value = file
        localPreviewUrl.value = URL.createObjectURL(file)
        state.value = UploadState.PENDING
        progress.value = 0
        error.value = null
        evidence.value = null
    }

    async function upload() {
        if (!uploadedFile.value) return
        state.value = UploadState.UPLOADING
        progress.value = 0
        error.value = null

        try {
            const formData = new FormData()
            formData.append('image', uploadedFile.value)

            const response = await axios.post(route('chat.evidence.store'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        progress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                    }
                },
            })

            if (response.data.success) {
                evidence.value = response.data.evidence
                state.value = UploadState.UPLOADED
                // JANGAN revoke localPreviewUrl di sini — masih dipakai optimistic bubble
                // Biarkan Index.vue:handleSend() yang pakai previewBefore (blob valid)
                // dan baru revoke saat resetUpload() setelah sendEvidenceMessage selesai
            } else {
                throw new Error(response.data.message || t('chat.error.uploadFailed'))
            }
        } catch (err) {
            state.value = UploadState.FAILED
            error.value = err.response?.data?.message || err.message || t('chat.error.uploadFailed')
        }
    }

    function retry() {
        if (!uploadedFile.value) return
        if (localPreviewUrl.value) {
            URL.revokeObjectURL(localPreviewUrl.value)
        }
        localPreviewUrl.value = URL.createObjectURL(uploadedFile.value)
        upload()
    }

    function setEvidenceFromShare(evidenceData) {
        if (localPreviewUrl.value) {
            URL.revokeObjectURL(localPreviewUrl.value)
        }
        evidence.value = evidenceData
        localPreviewUrl.value = evidenceData.url ?? null
        uploadedFile.value = null
        state.value = UploadState.UPLOADED
        progress.value = 100
        error.value = null
    }

    function reset() {
        if (localPreviewUrl.value) {
            URL.revokeObjectURL(localPreviewUrl.value)
        }
        state.value = UploadState.PENDING
        progress.value = 0
        error.value = null
        evidence.value = null
        localPreviewUrl.value = null
        uploadedFile.value = null
    }

    function cleanup() {
        if (localPreviewUrl.value) {
            URL.revokeObjectURL(localPreviewUrl.value)
        }
    }

    return {
        state,
        progress,
        error,
        evidence,
        localPreviewUrl,
        uploadedFile,
        isUploading,
        isPending,
        isUploaded,
        isProcessing,
        isReady,
        isFailed,
        isDone,
        setFile,
        setEvidenceFromShare,
        upload,
        retry,
        reset,
        cleanup,
    }
}
