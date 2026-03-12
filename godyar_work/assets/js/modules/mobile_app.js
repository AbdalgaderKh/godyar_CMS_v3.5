

(function () {
  window.__godyar_js_loaded = window.__godyar_js_loaded || {};
  if (window.__godyar_js_loaded['mobile_app']) return;
  window.__godyar_js_loaded['mobile_app'] = true;

  if (window.__gdyMobileAppLoaded) return;
  window.__gdyMobileAppLoaded = true;

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var menuBtn = document.querySelector('.mobile-menu-btn');
    var mobileNav = document.querySelector('.mobile-nav');

    if (menuBtn && mobileNav) {
      menuBtn.addEventListener('click', function () {
        mobileNav.classList.toggle('active');
      });
    }

    var backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
      backToTop.addEventListener('click', function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  });
})();
