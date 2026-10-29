/**
 * useApiCache.js
 *
 * In-memory cache sederhana untuk endpoint axios (Stale-While-Revalidate).
 * Menghilangkan skeleton kedua dan loading berulang saat bolak-balik halaman.
 */
const cache = new Map()

export function cachedGet(url, ttlMs = 60000) {
    const cached = cache.get(url)
    const now = Date.now()

    if (cached && (now - cached.timestamp < ttlMs)) {
        return Promise.resolve(cached.data)
    }

    return axios.get(url).then(res => {
        cache.set(url, { timestamp: Date.now(), data: res.data })
        return res.data
    }).catch(err => {
        // Jika jaringan gagal tapi ada cache lama, kembalikan cache lama sebagai fallback
        if (cached) return cached.data
        throw err
    })
}

export function invalidateApiCache(urlPrefix) {
    if (!urlPrefix) {
        cache.clear()
        return
    }
    for (const key of cache.keys()) {
        if (key.includes(urlPrefix)) {
            cache.delete(key)
        }
    }
}
