
(function () {
  function qs(sel) { return document.querySelector(sel); }

  function setStatus(isOnline) {
    var el = qs('.status');
    if (!el) return;

    if (isOnline) {
      el.textContent = 'تم الاتصال. جارٍ إعادة التحميل...';
      setTimeout(function () {
        try { window.location.reload(); } catch (e) {}
      }, 400);
    } else {
      el.textContent = 'أنت غير متصل بالإنترنت.';
    }
  }

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    var btn = document.getElementById('btnReload');
    if (btn) {
      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        try { window.location.reload(); } catch (e) {}
      });
    }

    setStatus(navigator.onLine);

    window.addEventListener('online', function () { setStatus(true); });
    window.addEventListener('offline', function () { setStatus(false); });
  });
})();
