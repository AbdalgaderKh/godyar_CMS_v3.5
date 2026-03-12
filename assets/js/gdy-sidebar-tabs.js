
(function(){
  function init(root){
    var btns = root.querySelectorAll('[data-tab-btn]');
    var panes = root.querySelectorAll('[data-tab-pane]');
    function activate(name){
      btns.forEach(function(b){
        b.classList.toggle('active', b.getAttribute('data-tab-btn') === name);
      });
      panes.forEach(function(p){
        p.classList.toggle('active', p.getAttribute('data-tab-pane') === name);
      });
      try{ localStorage.setItem('gdy_sidebar_tab', name); }catch(e){}
    }
    btns.forEach(function(b){
      b.addEventListener('click', function(){
        activate(b.getAttribute('data-tab-btn'));
      });
    });
    try{
      var stored = localStorage.getItem('gdy_sidebar_tab');
      if(stored) activate(stored);
    }catch(e){}
  }

  function boot(){
    document.querySelectorAll('[data-gdy-tabs]').forEach(init);
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
