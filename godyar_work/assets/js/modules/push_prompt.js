

'use strict';

let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;

  const btn = document.querySelector('[data-pwa-install]');
  if (!btn) return;

  btn.style.display = '';

  btn.addEventListener(
    'click',
    async () => {
      if (!deferredPrompt) return;

      try {
        deferredPrompt.prompt?.();
        if (deferredPrompt.userChoice?.then) await deferredPrompt.userChoice;
      } catch (_) {
      } finally {
        deferredPrompt = null;
        btn.style.display = 'none';
      }
    },
    { once: true }
  );
});

window.addEventListener('appinstalled', () => {
  deferredPrompt = null;
  const btn = document.querySelector('[data-pwa-install]');
  if (btn) btn.style.display = 'none';
});
