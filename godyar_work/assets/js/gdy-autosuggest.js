
(function(){
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  function debounce(fn, ms){
    var t; return function(){
      var args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(function(){ fn.apply(ctx, args); }, ms);
    };
  }

  function normalizeText(s){
    return (s||'').replace(/\s+/g,' ').trim();
  }

  function extractSuggestionsFromHTML(html, limit){
    var dom = document.implementation.createHTMLDocument('');
    dom.documentElement.innerHTML = html;

    var selectors = [
      '.news-card a', '.news-card__title', '.gdy-news-card__title a',
      'main a[href]', 'article a[href]'
    ];

    var links = [];
    selectors.some(function(sel){
      var els = qsa(sel, dom);
      if(!els.length) return false;
      els.forEach(function(el){
        var a = el.tagName === 'A' ? el : el.closest('a');
        if(!a) return;
        var href = a.getAttribute('href') || '';
        var title = normalizeText(a.textContent);
        if(title.length < 12) return;
        links.push({title:title, href:href});
      });
      return links.length > 0;
    });

    var seen = new Set();
    var out = [];
    for(var i=0;i<links.length && out.length<limit;i++){
      var k = links[i].title.toLowerCase();
      if(seen.has(k)) continue;
      seen.add(k);
      out.push(links[i]);
    }
    return out;
  }

  function setup(){
    var input = qs('#hdrSearchQ');
    if(!input) return;
    var form = input.closest('form');
    if(!form) return;

    form.style.position = 'relative';

    var dd = document.createElement('div');
    dd.className = 'gdy-suggest hidden';
    dd.setAttribute('role','listbox');
    form.appendChild(dd);

    function hide(){ dd.classList.add('hidden'); dd.innerHTML=''; }
    function show(items){
      if(!items || !items.length){ hide(); return; }
      dd.innerHTML = items.map(function(it){
        var href = it.href || '#';
        var title = it.title || '';
        var meta = it.meta || '';
        var badge = it.badge || '';
        return (
          '<a class="gdy-suggest__item" role="option" href="'+href.replace(/"/g,'&quot;')+'">'+
            (badge?'<span class="gdy-suggest__badge">'+badge+'</span>':'')+
            '<div>'+
              '<div class="gdy-suggest__title">'+title.replace(/</g,'&lt;')+'</div>'+
              (meta?'<div class="gdy-suggest__meta">'+meta.replace(/</g,'&lt;')+'</div>':'')+
            '</div>'+
          '</a>'
        );
      }).join('');
      dd.classList.remove('hidden');
    }

    async function fetchSuggest(q){
      var url = form.getAttribute('action') + '?q=' + encodeURIComponent(q) + '&suggest=1';
      try{
        var r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        var ct = (r.headers.get('content-type')||'').toLowerCase();
        var txt = await r.text();

        if(ct.includes('application/json') || (txt.trim().startsWith('[') || txt.trim().startsWith('{'))){
          try{
            var data = JSON.parse(txt);
            var arr = Array.isArray(data) ? data : (data.items||[]);
            return (arr||[]).slice(0,6).map(function(x){
              return { title: x.title || x.name || '', href: x.url || x.href || '#', meta: x.meta || '', badge: x.badge || '' };
            }).filter(function(x){ return x.title && x.href; });
          }catch(e){}
        }

        var items = extractSuggestionsFromHTML(txt, 6);
        items = items.map(function(it){
          try{ it.href = new URL(it.href, window.location.origin).toString(); }catch(e){}
          return it;
        });
        return items;
      }catch(e){
        return [];
      }
    }

    var run = debounce(async function(){
      var q = normalizeText(input.value);
      if(q.length < 2){ hide(); return; }
      var items = await fetchSuggest(q);
      show(items);
    }, 220);

    input.addEventListener('input', run);
    input.addEventListener('focus', run);
    input.addEventListener('blur', function(){ setTimeout(hide, 160); });
    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') hide();
    });
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
  else setup();
})();
