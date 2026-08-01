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

async function getRegistration() {
  if (swRegistration) return swRegistration;
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return null;
  try {
    await navigator.serviceWorker.register('/sw.js');
    swRegistration = await navigator.serviceWorker.ready;
  } catch (e) {
    console.warn('[push] service worker gagal diregistrasi', e);
    swRegistration = null;
  }
  return swRegistration;
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
    busy.value = true;
    try {
      if (!isSupported) return false;

      if (!vapidPublicKey.value) {
        showToast('VAPID belum dikonfigurasi di server.', 'error');
        return false;
      }

      permission.value = await Notification.requestPermission();
      if (permission.value !== 'granted') return false;

      const registration = await getRegistration();
      if (!registration) return false;

      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: base64UrlToUint8Array(vapidPublicKey.value),
      });

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
      busy.value = false;
    }
  };

  const disablePush = async () => {
    if (busy.value) return;
    busy.value = true;
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
      busy.value = false;
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
