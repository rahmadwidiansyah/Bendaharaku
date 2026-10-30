import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * State tabel admin yang disinkronkan dengan URL.
 *
 * Kontrak: `?sort=<key>&direction=asc|desc&page=&per_page=&<filters>`.
 * Semua perubahan sort/filter reset ke halaman 1 dan mempertahankan filter
 * lain yang sedang aktif.
 */
export function useAdminTable() {
    const page = usePage()

    const query = computed(() =>
        Object.fromEntries(new URLSearchParams(page.url.split('?')[1] || '')),
    )

    const path = computed(() => page.url.split('?')[0])

    const navigate = (overrides = {}, { resetPage = true } = {}) => {
        const params = { ...query.value, ...overrides }

        if (resetPage && !Object.prototype.hasOwnProperty.call(overrides, 'page')) {
            delete params.page
        }

        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                delete params[key]
            }
        })

        router.get(path.value, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }

    const sortBy = (key) => {
        const currentSort = query.value.sort
        const currentDirection = query.value.direction || 'asc'

        if (currentSort !== key) {
            return navigate({ sort: key, direction: 'asc' })
        }

        if (currentDirection === 'asc') {
            return navigate({ sort: key, direction: 'desc' })
        }

        // Klik ketiga: kembali ke urutan default server.
        return navigate({ sort: undefined, direction: undefined })
    }

    const reset = () => {
        router.get(path.value, {}, { preserveState: true, preserveScroll: true, replace: true })
    }

    return { query, path, navigate, sortBy, reset }
}
