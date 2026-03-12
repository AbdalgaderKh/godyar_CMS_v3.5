
(function () {
  function isStandalone() {
    try {
      var iosStandalone = !!(window.navigator && window.navigator.standalone === true);
      var mql = window.matchMedia ? window.matchMedia('(display-mode: standalone)') : null;
      var displayStandalone = !!(mql && mql.matches);
      return iosStandalone || displayStandalone;
    } catch (e) {
      return false;
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
    var el = document.getElementById('gdy-splash');
    if (!el) return;

    if (!isStandalone()) {
      try { el.parentNode && el.parentNode.removeChild(el); } catch (e) {}
      return;
    }

    el.classList.add('is-visible');

    function hide() {
      try {
        el.classList.add('is-hiding');
        setTimeout(function () {
          try { el.parentNode && el.parentNode.removeChild(el); } catch (e) {}
        }, 320);
      } catch (e) {
        try { el.parentNode && el.parentNode.removeChild(el); } catch (_) {}
      }
    }

    setTimeout(hide, 350);
    setTimeout(hide, 2200);
  });
})();
