
(function(){
  function isArticle(){
    var a = document.querySelector('article');
    if(a && (a.innerText || '').trim().length > 800) return true;
    if(document.querySelector('.news-article, .post, .post-content, .article, .article-content, [data-article]')) return true;
    return false;
  }

  function setup(){
    if(!isArticle()) return;
    if(document.getElementById('gdyReadProgress')) return;

    var bar = document.createElement('div');
    bar.id = 'gdyReadProgress';
    bar.setAttribute('aria-hidden','true');
    bar.innerHTML = '<span></span>';
    document.body.appendChild(bar);

    var fill = bar.querySelector('span');

    function update(){
      var doc = document.documentElement;
      var scrollTop = (window.pageYOffset || doc.scrollTop || 0);
      var height = Math.max(doc.scrollHeight - window.innerHeight, 1);
      var p = Math.min(100, Math.max(0, (scrollTop / height) * 100));
      fill.style.width = p.toFixed(2) + '%';
      bar.style.opacity = (scrollTop > 24) ? '1' : '0';
    }
    update();
    window.addEventListener('scroll', update, {passive:true});
    window.addEventListener('resize', update);
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
  else setup();
})();
