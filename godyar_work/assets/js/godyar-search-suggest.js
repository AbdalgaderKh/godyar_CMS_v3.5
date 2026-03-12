(function(){
  'use strict';

  function initMoreMenu(){
    var root = document.querySelector('[data-more-menu]');
    if(!root) return;

    var btn = root.querySelector('[data-more-btn]');
    var panel = root.querySelector('[data-more-menu-panel]');
    if(!btn || !panel) return;

    function close(){
      btn.setAttribute('aria-expanded','false');
      panel.classList.remove('is-open');
    }
    function toggle(){
      var open = panel.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    btn.addEventListener('click', function(e){
      e.preventDefault();
      toggle();
    });

    document.addEventListener('click', function(e){
      if(!root.contains(e.target)) close();
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') close();
    });
  }

  function initSearchSuggest(){
    var input = document.getElementById('gdyOverlaySearchQ') || document.getElementById('hdrSearchQInline');
    if(!input) return;

    var isOverlay = (input.id === 'gdyOverlaySearchQ');
    var root = isOverlay ? document.querySelector('[data-search-overlay]') : input.closest('form');
    if(!root) return;

    var box;
    if(isOverlay){
      box = root.querySelector('[data-search-suggest]');
      if(!box) return;
      box.innerHTML = '<div class="gdy-suggest__inner" role="listbox" aria-label="اقتراحات البحث"></div>';
    } else {
      var form = input.closest('form');
      if(!form) return;
      box = document.createElement('div');
      box.className = 'gdy-suggest';
      box.innerHTML = '<div class="gdy-suggest__inner" role="listbox" aria-label="اقتراحات البحث"></div>';
      form.appendChild(box);
    }

    var list = box.querySelector('.gdy-suggest__inner');
    var timer = null;
    var last = '';

    function hide(){ box.classList.remove('is-open'); list.innerHTML=''; }
    function show(){ box.classList.add('is-open'); }

    function render(items){
      if(!items || !items.length){ hide(); return; }
      list.innerHTML = items.map(function(it){
        var title = (it.title || it.name || '').toString();
        var url = (it.url || it.href || '').toString();
        if(!title) return '';
        if(!url){
          url = (document.body.getAttribute('data-base-url') || '') + '/search?q=' + encodeURIComponent(title);
        }
        return '<a class="gdy-suggest__item" role="option" href="'+escapeHtml(url)+'">'+escapeHtml(title)+'</a>';
      }).join('');
      show();
    }

    function escapeHtml(s){
      return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

    function fetchSuggest(q){
      var url = (window.GDY_BASE_URL || '') + '/api/search/suggest?q=' + encodeURIComponent(q);
      return fetch(url, {credentials:'same-origin'}).then(function(r){ return r.json(); }).catch(function(){ return []; });
    }

    input.addEventListener('input', function(){
      var q = (input.value||'').trim();
      if(q.length < 2){ hide(); return; }
      if(q === last) return;
      last = q;
      if(timer) clearTimeout(timer);
      timer = setTimeout(function(){
        fetchSuggest(q).then(function(data){
          var items = Array.isArray(data) ? data : (data && data.items) ? data.items : [];
          render(items);
        });
      }, 180);
    });

    input.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){ hide(); }
    });

    document.addEventListener('click', function(e){
      if(!box.contains(e.target) && e.target !== input){ hide(); }
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    initMoreMenu();
    initSearchSuggest();
  });
})();