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

  var nodes = document.querySelectorAll('.sidebar, .admin-sidebar, .app-sidebar, aside.sidebar, aside.admin-sidebar, [class*="sidebar"]');
  for (var i = 0; i < nodes.length; i++) {
    nodes[i].classList.remove('collapsed');
    nodes[i].classList.remove('is-collapsed');
    nodes[i].style.width = '300px';
    nodes[i].style.minWidth = '300px';
  }

  try {
    localStorage.setItem('sidebar-collapsed', '0');
    localStorage.setItem('sidebar-mini', '0');
    localStorage.setItem('compact-sidebar', '0');
    localStorage.setItem('menu-collapsed', '0');
  } catch (e) {}
});
