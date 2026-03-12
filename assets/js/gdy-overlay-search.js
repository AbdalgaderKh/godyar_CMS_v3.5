(function(){
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  var overlay = qs('[data-search-overlay]');
  if (!overlay) return;

  var openBtns = qsa('[data-search-open]');
  var closeBtns = qsa('[data-search-close]', overlay);
  var input = qs('#gdyOverlaySearchQ', overlay);

  function open(){
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden','false');
    document.documentElement.classList.add('gdy-search-open');
    try{ setTimeout(function(){ input && input.focus(); }, 30); }catch(e){}
  }
  function close(){
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden','true');
    document.documentElement.classList.remove('gdy-search-open');
  }

  openBtns.forEach(function(b){ b.addEventListener('click', function(e){ e.preventDefault(); open(); }); });
  closeBtns.forEach(function(b){ b.addEventListener('click', function(e){ e.preventDefault(); close(); }); });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
  });
})();