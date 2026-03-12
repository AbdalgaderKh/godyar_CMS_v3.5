
(function(){
  function normalizeUses(){
    try{
      var sprite = window.GDY_ICON_SPRITE || '';
      var uses = document.querySelectorAll('svg use');
      uses.forEach(function(u){
        var href = u.getAttribute('href') || u.getAttribute('xlink:href') || '';
        if(!href) return;

        var hash = href.indexOf('#');
        if(hash > -1){
          var id = href.slice(hash);
          u.setAttribute('href', id);
          u.setAttribute('xlink:href', id);
          return;
        }

        if(href.charAt(0) === '#') return;

        u.setAttribute('href', '#' + href);
        u.setAttribute('xlink:href', '#' + href);
      });
    }catch(e){}
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', normalizeUses);
  }else{
    normalizeUses();
  }
})();
