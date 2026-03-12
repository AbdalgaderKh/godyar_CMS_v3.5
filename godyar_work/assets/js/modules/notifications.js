

'use strict';

const pubKey = window.GDY_VAPID_PUBLIC_KEY || '';
if (pubKey) {
  const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4-(base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; i += 1) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
  };

  const ensureSw = () => {
    if (!('serviceWorker' in navigator)) throw new Error('no-sw');
    return navigator.serviceWorker.ready;
  };

  const subscribe = async (prefs) => {
    const reg = await ensureSw();
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') throw new Error('denied');

    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(pubKey)
    });

    var base = String(window.GDY_BASE || '').replace(/\/$/, '');
    await fetch(base + '/api/push/subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify({ ...sub.toJSON(), prefs: prefs || {} })
    });

    return sub;
  };

  const unsubscribe = async () => {
    const reg = await ensureSw();
    const sub = await reg.pushManager.getSubscription();
    if (!sub) return;

    try {
      var base2 = String(window.GDY_BASE || '').replace(/\/$/, '');
      await fetch(base2 + '/api/push/unsubscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: JSON.stringify({ endpoint: sub.endpoint })
      });
    } catch (_) {
    }

    await sub.unsubscribe();
  };

  window.GodyarPush = { subscribe, unsubscribe };
}
