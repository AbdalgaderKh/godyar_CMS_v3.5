(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var listBtn = document.getElementById('gdyListViewBtn');
    var cardBtn = document.getElementById('gdyCardViewBtn');
    var tableWrap = document.getElementById('gdyNewsTableWrap');
    var cardsWrap = document.getElementById('gdyNewsCards');
    if (!tableWrap || !cardsWrap || !listBtn || !cardBtn) return;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.table-news tbody tr[data-news-id]'));
    cardsWrap.innerHTML = rows.map(function(row){
      var title=row.getAttribute('data-news-title')||'';
      var excerpt=row.getAttribute('data-news-excerpt')||'';
      var status=row.getAttribute('data-news-status')||'';
      var date=row.getAttribute('data-news-date')||'';
      var url=row.getAttribute('data-news-url')||'#';
      var edit=row.getAttribute('data-news-edit')||'#';
      return '<article class="gdy-news-card"><h3>'+title+'</h3><p>'+excerpt+'</p><div class="gdy-news-card-meta"><span>'+status+'</span><span>'+date+'</span></div><div class="gdy-news-card-actions"><a class="btn btn-sm btn-outline-info" target="_blank" rel="noopener" href="'+url+'">عرض</a><a class="btn btn-sm btn-outline-primary" href="'+edit+'">تعديل</a></div></article>';
    }).join('');
    function activate(mode){
      var cards = mode === 'cards';
      cardsWrap.classList.toggle('d-none', !cards);
      tableWrap.classList.toggle('d-none', cards);
      listBtn.classList.toggle('active', !cards);
      cardBtn.classList.toggle('active', cards);
      try{ localStorage.setItem('gdy-news-view', mode); }catch(e){}
    }
    listBtn.addEventListener('click', function(){ activate('list'); });
    cardBtn.addEventListener('click', function(){ activate('cards'); });
    try{ activate(localStorage.getItem('gdy-news-view') || 'list'); }catch(e){ activate('list'); }
  });
})();
