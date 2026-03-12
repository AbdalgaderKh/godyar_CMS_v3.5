

function __gdyApplyImgSizing(img){
  if(!img || !img.style) return;
  try{
    var inRatio = img.closest && img.closest('.ratio');
    var inCard  = img.closest && img.closest('.card');
    if(inRatio || inCard){
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'cover';
      img.style.display = 'block';
      return;
    }
  }catch(e){}
  img.style.maxWidth = '100%';
  img.style.height = 'auto';
  img.style.display = 'block';
}

'use strict';

const ATTR_FALLBACK = 'data-gdy-fallback-src';
const ATTR_HIDE = 'data-gdy-hide-onerror';
const ATTR_SHOW = 'data-gdy-show-onload';
const ATTR_BOUND = 'data-gdy-fallback-bound';
const ATTR_TRIED = 'data-gdy-fallback-tried';

const isImg = (el) => el?.tagName?.toLowerCase() === 'img';

const shouldManage = (img) =>
  img.hasAttribute(ATTR_FALLBACK) || img.hasAttribute(ATTR_HIDE) || img.hasAttribute(ATTR_SHOW);

const safeSetHidden = (img, hidden) => {
  try {
    if (!img?.style) return;
    img.style.display = hidden ? 'none' : '';
  } catch (_) {
  }
};

const safeSetOpacity = (img, value) => {
  try {
    if (!img?.style) return;
    img.style.opacity = value;
  } catch (_) {
  }
};

const onLoad = (img) => {
  if (img.getAttribute(ATTR_SHOW) === '1') safeSetOpacity(img, '');
};

const onError = (img) => {
  const fallback = img.getAttribute(ATTR_FALLBACK);
  const tried = img.getAttribute(ATTR_TRIED) === '1';

  if (fallback && !tried) {
    img.setAttribute(ATTR_TRIED, '1');
    const current = String(img.getAttribute('src') || '').trim();
    if (current !== fallback) {
      img.setAttribute('src', fallback);
      return;
    }
  }

  if (img.getAttribute(ATTR_HIDE) === '1') safeSetHidden(img, true);
};

const bindOne = (img) => {
  if (!img || !isImg(img) || !shouldManage(img)) return;
  if (img.getAttribute(ATTR_BOUND) === '1') return;
  img.setAttribute(ATTR_BOUND, '1');

  if (img.getAttribute(ATTR_SHOW) === '1') safeSetOpacity(img, '0');

  img.addEventListener('load', () => onLoad(img), { passive: true });
  img.addEventListener('error', () => onError(img), { passive: true });

  if (img.complete && img.naturalWidth > 0) onLoad(img);
};

const scan = (root) => {
  const scope = root?.querySelectorAll ? root : document;
  const imgs = scope.querySelectorAll(`img[${ATTR_FALLBACK}],img[${ATTR_HIDE}],img[${ATTR_SHOW}]`);
  for (const img of imgs) bindOne(img);
};

const init = () => {
  scan(document);

  if (typeof MutationObserver === 'function') {
    const obs = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (!m.addedNodes) continue;
        for (const n of m.addedNodes) {
          if (isImg(n)) bindOne(n);
          else if (n?.querySelectorAll) scan(n);
        }
      }
    });
    obs.observe(document.documentElement || document.body, { childList: true, subtree: true });
  }
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();

document.addEventListener('DOMContentLoaded', function(){
  try{
    var imgs = document.querySelectorAll('img[data-gdy-hide-onerror], img[data-gdy-fallback-bound]');
    imgs.forEach(function(img){
      __gdyApplyImgSizing(img);
      try{
        var parent = img.parentElement;
        if(parent){
          var same = parent.querySelectorAll('img[src="' + img.getAttribute('src') + '"]');
          if(same && same.length > 1){
            for(var i=1;i<same.length;i++){
              same[i].style.display = 'none';
            }
          }
        }
      }catch(e){}
    });
  }catch(e){}
});

(function(){
  function closestMediaRoot(el){
    if(!el || !el.closest) return null;
    return el.closest('.ratio, figure, a, .card, article, .col, .row, .container, #mainContent, body');
  }
  function sameSrcImgs(root, src){
    if(!root || !src) return [];
    try{
      return Array.prototype.slice.call(root.querySelectorAll('img[src="' + src.replace(/"/g,'\"') + '"]'));
    }catch(e){
      try{
        var all = Array.prototype.slice.call(root.querySelectorAll('img'));
        return all.filter(function(i){ return i.getAttribute('src') === src; });
      }catch(e2){ return []; }
    }
  }

  function runStrong(){
    try{
      var imgs = document.querySelectorAll('img[data-gdy-fallback-bound], img[data-gdy-hide-onerror]');
      imgs.forEach(function(img){
        var src = img.getAttribute('src');
        var root = closestMediaRoot(img) || img.parentElement;
        if(!root) return;
        var list = sameSrcImgs(root, src);
        if(list.length > 1){
          var keep = null;
          list.forEach(function(it){
            if(keep) return;
            try{
              if(it.closest && it.closest('.ratio')) keep = it;
              else if((it.getAttribute('style')||'').indexOf('object-fit:cover') !== -1) keep = it;
            }catch(e){}
          });
          if(!keep) keep = list[0];
          list.forEach(function(it){
            if(it === keep) return;
            it.style.display = 'none';
          });
        }
      });
    }catch(e){}
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', runStrong);
  } else {
    runStrong();
  }
})();
