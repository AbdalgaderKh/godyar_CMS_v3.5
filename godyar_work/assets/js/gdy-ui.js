
(function(){
  'use strict';

  var LS_KEY = 'gdy_theme';

  function getRoot(){ return document.documentElement; }

  function setTheme(theme){
    var root = getRoot();
    if (!root) return;
    var t = (theme === 'dark') ? 'dark' : 'light';
    root.setAttribute('data-theme', t);
    document.body && document.body.classList.toggle('is-dark', t === 'dark');

    try { localStorage.setItem(LS_KEY, t); } catch(_e) {}

    document.querySelectorAll('[data-gdy-theme-toggle], [data-action="theme"], #gdyTabTheme').forEach(function(btn){
      try { btn.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false'); } catch(_e) {}
    });
  }

  function toggleTheme(){
    var root = getRoot();
    var cur = (root && root.getAttribute('data-theme')) || 'light';
    setTheme(cur === 'dark' ? 'light' : 'dark');
  }

  function initTheme(){
    var root = getRoot();
    if (!root) return;
    var saved = '';
    try { saved = localStorage.getItem(LS_KEY) || ''; } catch(_e) {}
    if (saved === 'dark' || saved === 'light') {
      setTheme(saved);
    }
  }

  function qs(id){ return document.getElementById(id); }

  function openMobileSearch(){
    var box = qs('gdyMobileSearch');
    if (!box) return;
    box.hidden = false;
    box.classList.add('open');
    var input = qs('gdyMobileSearchInput');
    if (input) { try { input.focus(); } catch(_e) {} }
  }

  function closeMobileSearch(){
    var box = qs('gdyMobileSearch');
    if (!box) return;
    box.hidden = true;
    box.classList.remove('open');
  }

  function backToTop(){
    try {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch(_e) {
      window.scrollTo(0, 0);
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    initTheme();

    document.addEventListener('click', function(e){
      var tgl = e.target.closest && e.target.closest('[data-gdy-theme-toggle], [data-action="theme"], #gdyTabTheme');
      if (tgl) {
        e.preventDefault();
        toggleTheme();
        return;
      }

      var msBtn = e.target.closest && e.target.closest('[data-mobile-search-btn], #gdyMobileSearchBtn');
      if (msBtn) {
        e.preventDefault();
        openMobileSearch();
        return;
      }

      var msClose = e.target.closest && e.target.closest('#gdyMobileSearchClose, .gdy-search__close');
      if (msClose) {
        e.preventDefault();
        closeMobileSearch();
        return;
      }

      var bt = e.target.closest && e.target.closest('#gdyBackTop, .gdy-backtop');
      if (bt) {
        e.preventDefault();
        backToTop();
        return;
      }

      var box = qs('gdyMobileSearch');
      if (box && !box.hidden) {
        if (e.target === box) {
          closeMobileSearch();
          return;
        }
      }
    }, true);

    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape') closeMobileSearch();
    });
  });
})();
