(function(){
  var root = document.body;
  if (!root) return;
  var key = 'godyar-theme';
  try {
    var saved = localStorage.getItem(key);
    if (saved === 'dark' || saved === 'light') {
      root.setAttribute('data-theme', saved);
    }
  } catch (e) {}

  var toggle = document.querySelector('[data-theme-toggle]');
  if (toggle) {
    toggle.addEventListener('click', function(){
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem(key, next); } catch (e) {}
      toggle.textContent = next === 'dark' ? '☀️' : '🌙';
    });
    toggle.textContent = root.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
  }

  var progress = document.querySelector('.g-v4-progress');
  function updateProgress(){
    if (!progress) return;
    var doc = document.documentElement;
    var max = doc.scrollHeight - doc.clientHeight;
    var value = max > 0 ? (doc.scrollTop / max) * 100 : 0;
    progress.style.width = value + '%';
  }
  updateProgress();
  window.addEventListener('scroll', updateProgress, {passive:true});

  function markLoaded(img){
    var shell = img.closest('.is-skeleton');
    if (shell) shell.classList.add('is-loaded');
  }
  var lazyImages = document.querySelectorAll('img[data-src]');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (!entry.isIntersecting) return;
        var img = entry.target;
        if (!img.getAttribute('src')) img.setAttribute('src', img.getAttribute('data-src'));
        if (img.complete) { markLoaded(img); }
        img.addEventListener('load', function(){ markLoaded(img); }, {once:true});
        io.unobserve(img);
      });
    }, {rootMargin:'150px 0px'});
    lazyImages.forEach(function(img){ io.observe(img); });
  } else {
    lazyImages.forEach(function(img){
      if (!img.getAttribute('src')) img.setAttribute('src', img.getAttribute('data-src'));
      if (img.complete) { markLoaded(img); }
      img.addEventListener('load', function(){ markLoaded(img); }, {once:true});
    });
  }

  var menuButton = document.querySelector('[data-mobile-toggle]');
  var nav = document.querySelector('[data-mobile-nav]');
  if (menuButton && nav) {
    menuButton.addEventListener('click', function(){
      nav.classList.toggle('is-open');
    });
    document.addEventListener('click', function(ev){
      if (!nav.classList.contains('is-open')) return;
      if (nav.contains(ev.target) || menuButton.contains(ev.target)) return;
      nav.classList.remove('is-open');
    });
  }

  var overlay = document.querySelector('[data-search-overlay]');
  var searchOpen = document.querySelector('[data-search-open]');
  var searchClose = document.querySelector('[data-search-close]');
  function setOverlay(open){
    if (!overlay) return;
    overlay.hidden = !open;
    root.classList.toggle('g-lock-scroll', open);
    if (open) {
      var input = overlay.querySelector('input[type="search"]');
      if (input) setTimeout(function(){ input.focus(); }, 10);
    }
  }
  if (searchOpen && overlay) searchOpen.addEventListener('click', function(){ setOverlay(true); });
  if (searchClose && overlay) searchClose.addEventListener('click', function(){ setOverlay(false); });
  if (overlay) {
    overlay.addEventListener('click', function(ev){ if (ev.target === overlay) setOverlay(false); });
    document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape') setOverlay(false); });
  }

  var previewRoot = document.querySelector('[data-preview-shell]');
  document.querySelectorAll('[data-preview-var]').forEach(function(input){
    input.addEventListener('input', function(){
      if (!previewRoot) return;
      previewRoot.style.setProperty(input.getAttribute('data-preview-var'), input.value);
    });
  });
})();
