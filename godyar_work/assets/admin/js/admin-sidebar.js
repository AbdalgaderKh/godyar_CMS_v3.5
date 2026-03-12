
document.addEventListener('DOMContentLoaded', function () {

  const openBtn = document.getElementById('gdyAdminMenuBtn');
  const closeBtn = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('gdyAdminBackdrop');

  function openSidebar(){
    document.body.classList.add('admin-sidebar-open');
    if (backdrop) backdrop.hidden = false;
  }
  function closeSidebar(){
    document.body.classList.remove('admin-sidebar-open');
    if (backdrop) backdrop.hidden = true;
  }

  if (openBtn) openBtn.addEventListener('click', openSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeSidebar();
  });

  document.querySelectorAll('.admin-sidebar a').forEach(function(a){
    a.addEventListener('click', function(){
      if (window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches) {
        closeSidebar();
      }
    });
  });

  const searchInput = document.getElementById('sidebarSearch');
  const searchResults = document.getElementById('sidebarSearchResults');
  const darkToggle = document.getElementById('darkModeToggle');
  const cards = Array.from(document.querySelectorAll('.admin-sidebar__link-card'));

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', function () {
      const q = (searchInput.value || '').trim().toLowerCase();
      searchResults.innerHTML = '';
      if (!q) {
        searchResults.style.display = 'none';
        return;
      }

      const matches = cards.filter(card => {
        const hay = (card.getAttribute('data-search') || '') + ' ' + (card.textContent || '');
        return hay.toLowerCase().includes(q);
      }).slice(0, 12);

      searchResults.style.display = 'block';
      if (!matches.length) {
        const div = document.createElement('div');
        div.className = 'admin-sidebar__search-result-item';
        div.textContent = 'لا توجد نتائج مطابقة';
        searchResults.appendChild(div);
        return;
      }

      matches.forEach(card => {
        const link = card.querySelector('a');
        if (!link) return;
        const labelEl = card.querySelector('.admin-sidebar__link-label');
        const label = labelEl ? labelEl.textContent.trim() : link.textContent.trim();

        const a = document.createElement('a');
        a.href = link.getAttribute('href');
        a.className = 'admin-sidebar__search-result-item';
        a.textContent = label;
        searchResults.appendChild(a);
      });
    });

    document.addEventListener('click', function (e) {
      if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.style.display = 'none';
      }
    });
  }

  if (darkToggle) {
    const html = document.documentElement;
    const KEY = 'gdy_admin_mode';
    try{
      const saved = localStorage.getItem(KEY);
      if (saved === 'light' || saved === 'dark') html.setAttribute('data-admin-mode', saved);
    }catch(_){}

    darkToggle.addEventListener('click', function () {
      const cur = html.getAttribute('data-admin-mode') || 'dark';
      const next = (cur === 'light') ? 'dark' : 'light';
      html.setAttribute('data-admin-mode', next);
      try{ localStorage.setItem(KEY, next); }catch(_){}
    });
  }
});
