(function(){
  var btn = document.getElementById('gdyBackTop');
  function onScroll(){
    if(!btn) return;
    if(window.scrollY > 500){ btn.classList.add('show'); }
    else { btn.classList.remove('show'); }
  }
  if(btn){
    btn.addEventListener('click', function(){
      try{ window.scrollTo({top:0, behavior:'smooth'}); }
      catch(e){ window.scrollTo(0,0); }
    });
    window.addEventListener('scroll', onScroll, {passive:true});
    onScroll();
  }

  var tbtn = document.querySelector('[data-gdy-theme-toggle]');
  if(tbtn){
    tbtn.addEventListener('click', function(){
      try{
        var cur = document.documentElement.getAttribute('data-theme') || 'light';
        var next = (cur === 'dark') ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('gdy_theme', next);
      }catch(e){}
    });
  }
})();
