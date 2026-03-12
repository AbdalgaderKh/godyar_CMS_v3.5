
document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  [
    'sidebar-collapsed',
    'sidebar-mini',
    'compact-sidebar',
    'menu-collapsed',
    'aside-collapsed'
  ].forEach(function (cls) {
    body.classList.remove(cls);
  });

  try {
    localStorage.removeItem('sidebar-collapsed');
    localStorage.removeItem('sidebar-mini');
    localStorage.removeItem('compact-sidebar');
    localStorage.removeItem('menu-collapsed');
    localStorage.setItem('sidebar-collapsed', '0');
  } catch (e) {}

  var selectors = [
    '.sidebar',
    '.admin-sidebar',
    '.app-sidebar',
    'aside.sidebar',
    'aside.admin-sidebar'
  ];

  selectors.forEach(function (sel) {
    var el = document.querySelector(sel);
    if (!el) return;
    el.style.width = '280px';
    el.style.minWidth = '280px';
    el.classList.remove('is-collapsed');
    el.classList.remove('collapsed');
  });
});
