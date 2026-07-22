import { ref, computed, reactive } from 'vue'
import axios from 'axios'

export const UploadState = {
    PENDING: 'PENDING',
    UPLOADING: 'UPLOADING',
    UPLOADED: 'UPLOADED',
    PROCESSING: 'PROCESSING',
    READY: 'READY',
    FAILED: 'FAILED',
}

export function useEvidenceUpload() {
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
                if (localPreviewUrl.value) {
                    URL.revokeObjectURL(localPreviewUrl.value)
                    localPreviewUrl.value = null
                }
            } else {
                throw new Error(response.data.message || 'Upload failed')
            }
        } catch (err) {
            state.value = UploadState.FAILED
            error.value = err.response?.data?.message || err.message || 'Upload gagal'
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
        upload,
        retry,
        reset,
        cleanup,
    }
}
