import { ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

/**
 * usePushNotifications — state & aksi Web Push di sisi browser.
 *
 * - enablePush(): minta izin browser → subscribe PushManager → simpan ke server
 * - disablePush(): unsubscribe PushManager + hapus dari server
 * - presence: visibilitychange visible→active, hidden→away + heartbeat 60s saat visible
 */

let swRegistration = null;

/**
 * Jalankan promise dengan batas waktu. Mencegah await yang menggantung
 * (mis. navigator.serviceWorker.ready yang tak pernah resolve) sehingga
 * state `busy` tidak selamanya true dan UI tak terkunci.
 */
function withTimeout(promise, ms = 8000, label = '') {
  return Promise.race([
    promise,
    new Promise((_, reject) => setTimeout(() => reject(new Error(`[push] timeout: ${label}`)), ms)),
  ]);
}

async function getRegistration(forceFresh = false) {
  if (swRegistration && !forceFresh) return swRegistration;
  const canRegister = 'serviceWorker' in navigator && 'PushManager' in window;
  if (!canRegister) return null;
  try {
    swRegistration = null;
    const registration = await withTimeout(navigator.serviceWorker.register('/sw.js'), 5000, 'register /sw.js');
    if (registration.active) {
      swRegistration = registration;
      return registration;
    }
    if (forceFresh && registration.installing) {
      try {
        await registration.unregister();
      } catch (e) { /* abaikan */ }
    }
    swRegistration = await withTimeout(navigator.serviceWorker.ready, 5000, 'ready');
    return swRegistration;
  } catch (e) {
    console.warn('[push] service worker gagal diregistrasi', e);
    swRegistration = null;
    return null;
  }
}

function base64UrlToUint8Array(base64Url) {
  const padding = '='.repeat((4 - (base64Url.length % 4)) % 4);
  const base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = atob(base64);
  const arr = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
  return arr;
}

export function usePushNotifications() {
  const { showToast } = useToast();

  const isSupported = typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  const permission = ref(isSupported ? Notification.permission : 'unsupported');
  const isSubscribed = ref(false);
  const vapidPublicKey = ref(null);
  const busy = ref(false);
  let busyTimer = null;

  const setBusy = (value) => {
    busy.value = value;
    if (value) {
      clearTimeout(busyTimer);
      busyTimer = setTimeout(() => {
        busy.value = false;
        console.warn('[push] busy watchdog: dikunci otomatis');
      }, 10000);
    } else {
      clearTimeout(busyTimer);
    }
  };

  const updateState = async () => {
    if (!isSupported) return;
    permission.value = Notification.permission;
    const registration = await getRegistration();
    if (!registration) return;
    const subscription = await registration.pushManager.getSubscription();
    isSubscribed.value = !!subscription;
  };

  const setVapidKey = (key) => {
    vapidPublicKey.value = key || null;
  };

  const enablePush = async () => {
    if (busy.value) return false;
    setBusy(true);
    try {
      if (!isSupported) return false;

      if (!vapidPublicKey.value) {
        showToast('VAPID belum dikonfigurasi di server.', 'error');
        return false;
      }

      permission.value = await Notification.requestPermission();
      if (permission.value !== 'granted') return false;

      let registration = await getRegistration();
      if (!registration) return false;

      let subscription;
      try {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: base64UrlToUint8Array(vapidPublicKey.value),
        });
      } catch (e) {
        // Service worker lama/rusak (redundant) sering membuat Chrome
        // melempar AbortError "no active Service Worker". Coba sekali lagi
        // dengan registrasi segar (unregister + re-register).
        console.warn('[push] subscribe pertama gagal, coba registrasi segar:', e);
        await new Promise((r) => setTimeout(r, 750));
        registration = await getRegistration(true);
        if (!registration) return false;
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: base64UrlToUint8Array(vapidPublicKey.value),
        });
      }

      await axios.post(route('notifications.subscribe'), {
        endpoint: subscription.endpoint,
        p256dh: subscription.toJSON().keys.p256dh,
        auth: subscription.toJSON().keys.auth,
      });

      isSubscribed.value = true;
      return true;
    } catch (e) {
      console.error('[push] subscribe gagal', e);
      showToast('Gagal mengaktifkan notifikasi.', 'error');
      return false;
    } finally {
      setBusy(false);
    }
  };

  const disablePush = async () => {
    if (busy.value) return;
    setBusy(true);
    try {
      const registration = await getRegistration();
      if (registration) {
        const subscription = await registration.pushManager.getSubscription();
        if (subscription) {
          try {
            await axios.post(route('notifications.unsubscribe'), { endpoint: subscription.endpoint });
          } catch (e) {
            console.warn('[push] unsubscribe server gagal', e);
          }
          await subscription.unsubscribe();
        }
      }
      isSubscribed.value = false;
    } finally {
      setBusy(false);
    }
  };

  let presenceTimer = null;
  let lastPresence = null;

  const sendPresence = (state) => {
    if (state === lastPresence) return;
    lastPresence = state;
    axios.post(route('notifications.presence'), { state }).catch(() => {});
  };

  const startPresence = () => {
    if (!isSupported || presenceTimer) return;
    const onVisibility = () => sendPresence(document.visibilityState === 'visible' ? 'active' : 'away');
    document.addEventListener('visibilitychange', onVisibility);
    sendPresence('active');
    presenceTimer = setInterval(() => {
      if (document.visibilityState === 'visible') sendPresence('active');
    }, 60000);
  };

  const stopPresence = () => {
    if (presenceTimer) {
      clearInterval(presenceTimer);
      presenceTimer = null;
    }
  };

  return {
    isSupported,
    permission,
    isSubscribed,
    busy,
    setVapidKey,
    updateState,
    enablePush,
    disablePush,
    startPresence,
    stopPresence,
  };
}
