
(function () {
  function forceExpandSidebar() {
    var body = document.body;
    [
      'sidebar-collapsed',
      'sidebar-mini',
      'compact-sidebar',
      'menu-collapsed',
      'aside-collapsed',
      'sidebar-closed',
      'mini-sidebar'
    ].forEach(function (cls) {
      body.classList.remove(cls);
    });

    try {
      [
        'sidebar-collapsed',
        'sidebar-mini',
        'compact-sidebar',
        'menu-collapsed',
        'aside-collapsed',
        'sidebar-closed',
        'mini-sidebar'
      ].forEach(function (k) {
        localStorage.removeItem(k);
      });
      localStorage.setItem('sidebar-collapsed', '0');
      localStorage.setItem('sidebar-mini', '0');
    } catch (e) {}

    [
      '.sidebar',
      '.admin-sidebar',
      '.app-sidebar',
      'aside.sidebar',
      'aside.admin-sidebar',
      '#sidebar',
      '#adminSidebar'
    ].forEach(function (sel) {
      var el = document.querySelector(sel);
      if (!el) return;
      el.classList.remove('is-collapsed');
      el.classList.remove('collapsed');
      el.style.width = '300px';
      el.style.minWidth = '300px';
      el.style.maxWidth = '300px';
      el.style.transform = 'none';

      var spans = el.querySelectorAll('span, .menu-text, .nav-text, .item-text, .label, .title');
      spans.forEach(function (s) {
        s.style.display = 'inline';
        s.style.opacity = '1';
        s.style.visibility = 'visible';
        s.style.width = 'auto';
        s.style.maxWidth = 'none';
        s.style.overflow = 'visible';
      });
    });
  }

  document.addEventListener('DOMContentLoaded', forceExpandSidebar);
  window.addEventListener('load', forceExpandSidebar);
  setTimeout(forceExpandSidebar, 300);
  setTimeout(forceExpandSidebar, 1000);
})();
