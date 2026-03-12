
'use strict';

(function(){
  function $(sel, root){ return (root||document).querySelector(sel); }
  function on(el, ev, fn){ if(el) el.addEventListener(ev, fn); }

  function toast(msg){
    try{
      var t = document.createElement('div');
      t.className = 'gdy-toast';
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(()=>t.classList.add('show'), 10);
      setTimeout(()=>{ t.classList.remove('show'); setTimeout(()=>t.remove(), 250); }, 2200);
    }catch(e){ alert(msg); }
  }

  async function copyText(txt){
    try{
      if (navigator.clipboard && window.isSecureContext){
        await navigator.clipboard.writeText(txt);
        return true;
      }
    }catch(e){}
    try{
      var ta=document.createElement('textarea');
      ta.value=txt;
      ta.style.position='fixed'; ta.style.left='-9999px';
      document.body.appendChild(ta);
      ta.focus(); ta.select();
      var ok=document.execCommand('copy');
      ta.remove();
      return !!ok;
    }catch(e){ return false; }
  }

  function buildShareFallback(url, title){
    var u = encodeURIComponent(url);
    var t = encodeURIComponent(title || '');
    return [
      {label:'فيسبوك', href:'https://www.facebook.com/sharer/sharer.php?u='+u},
      {label:'X', href:'https://x.com/intent/post?url='+u+'&text='+t},
      {label:'واتساب', href:'https://wa.me/?text='+u}
    ];
  }

  function initReportTools(){
    var copyBtn = $('#gdyCopyLink');
    var shareBtn = $('#gdyShare');
    var bmBtn   = $('#gdyBookmark');
    var printBtn= $('#gdyPrint');
    var pdfBtn  = $('#gdyPdf');
    var readBtn = $('#gdyReadingMode');
    var incBtn  = $('#gdyFontInc');
    var decBtn  = $('#gdyFontDec');
    var qrBtn   = $('#gdyQrToggle');
    var aiBtn   = $('#gdyAiToggle');

    if(!copyBtn && !shareBtn && !bmBtn && !printBtn && !readBtn && !incBtn && !decBtn) return;

    var pageUrl = (bmBtn && bmBtn.getAttribute('data-url')) || location.href;
    var title   = (bmBtn && bmBtn.getAttribute('data-title')) || document.title;

    on(copyBtn,'click', async function(){
      var ok = await copyText(pageUrl);
      toast(ok ? 'تم نسخ الرابط' : 'تعذر نسخ الرابط');
    });

    on(shareBtn,'click', async function(){
      try{
        if (navigator.share){
          await navigator.share({ title: title, url: pageUrl, text: title });
          return;
        }
      }catch(e){}
      var items = buildShareFallback(pageUrl, title);
      var html = items.map(i=>'<a class="gdy-share-item" target="_blank" rel="noopener" href="'+i.href+'">'+i.label+'</a>').join('');
      var box = document.createElement('div');
      box.className='gdy-share-pop';
      box.innerHTML = '<div class="gdy-share-pop__inner"><div class="gdy-share-pop__title">مشاركة</div>'+html+'<button type="button" class="gdy-share-close" aria-label="إغلاق">×</button></div>';
      document.body.appendChild(box);
      var close = box.querySelector('.gdy-share-close');
      function kill(){ box.remove(); document.removeEventListener('keydown', onEsc); }
      function onEsc(e){ if(e.key==='Escape') kill(); }
      on(close,'click',kill);
      on(box,'click', function(e){ if(e.target===box) kill(); });
      document.addEventListener('keydown', onEsc);
    });

    on(bmBtn,'click', async function(){
      var id = parseInt(bmBtn.getAttribute('data-news-id')||'0',10);
      if(!id){ toast('تعذر تحديد رقم الخبر'); return; }

      bmBtn.disabled = true;
      try{
        var res = await fetch('/api/bookmarks/toggle?id='+encodeURIComponent(String(id)), { credentials:'same-origin' });
        var j = await res.json().catch(()=>null);
        if(!j || !j.ok){ toast((j && j.message) ? j.message : 'تعذر تحديث الحفظ'); return; }
        var saved = !!j.bookmarked;
        var txtEl = bmBtn.querySelector('.gdy-bm-text');
        if(txtEl) txtEl.textContent = saved ? 'محفوظ' : 'حفظ';
        bmBtn.classList.toggle('is-saved', saved);
        toast(saved ? 'تم الحفظ' : 'تم إلغاء الحفظ');
      }catch(e){
        toast('حدث خطأ أثناء الحفظ');
      }finally{
        bmBtn.disabled = false;
      }
    });

    on(printBtn,'click', function(){ window.print(); });

    on(pdfBtn,'click', function(){
      var id = getNewsId();
      if(!id){ toast('تعذر تحديد رقم الخبر'); return; }
      var lang = document.documentElement.lang || 'ar';
      window.location.href = '/api/news/pdf?id=' + encodeURIComponent(id) + '&lang=' + encodeURIComponent(lang);
    });

    on(readBtn,'click', function(){
      document.documentElement.classList.toggle('gdy-reading-mode');
      toast(document.documentElement.classList.contains('gdy-reading-mode') ? 'تم تفعيل وضع القراءة' : 'تم إيقاف وضع القراءة');
    });

    var fontVar = '--gdy-article-font-scale';
    function getScale(){
      var v = getComputedStyle(document.documentElement).getPropertyValue(fontVar).trim();
      var n = parseFloat(v || '1');
      if(!isFinite(n) || n<=0) n=1;
      return n;
    }
    function setScale(n){
      n = Math.max(0.85, Math.min(1.35, n));
      document.documentElement.style.setProperty(fontVar, String(n));
      try{ localStorage.setItem('gdy_font_scale', String(n)); }catch(e){}
    }
    try{
      var saved = parseFloat(localStorage.getItem('gdy_font_scale')||'');
      if(isFinite(saved)) setScale(saved);
    }catch(e){}

    on(incBtn,'click', function(){ setScale(getScale()+0.05); });
    on(decBtn,'click', function(){ setScale(getScale()-0.05); });

    on(qrBtn,'click', function(){
      var qr = $('#gdyHeroQr');
      if(!qr) return;
      qr.toggleAttribute('hidden');
    });

    on(aiBtn,'click', function(){
      var box = $('#gdyAiBox');
      if(!box) return;
      var nowHidden = box.hasAttribute('hidden');
      if(nowHidden) box.removeAttribute('hidden');
      else box.setAttribute('hidden','');
      aiBtn.setAttribute('aria-expanded', nowHidden ? 'true' : 'false');
    });
  }

  function initTTS(){
    var wrap = $('#gdy-tts');
    if(!wrap) return;

    var playBtn = $('#gdy-tts-play');
    var stopBtn = $('#gdy-tts-stop');
    var rateInp = $('#gdy-tts-rate');
    var dlBtn   = $('#gdy-tts-download');
    var textEl  = $('#gdy-tts-text');

    if(!playBtn || !stopBtn || !textEl) return;

    var synth = window.speechSynthesis;
    var utter = null;

    function currentRate(){
      var r = parseFloat((rateInp && rateInp.value) || '1');
      if(!isFinite(r) || r<=0) r=1;
      return Math.max(0.7, Math.min(1.3, r));
    }

    function stop(){
      try{ if(synth) synth.cancel(); }catch(e){}
      utter=null;
    }

    on(playBtn,'click', function(){
      if(!synth || !window.SpeechSynthesisUtterance){
        toast('ميزة الاستماع غير مدعومة في هذا المتصفح');
        return;
      }
      stop();
      utter = new SpeechSynthesisUtterance(textEl.textContent || textEl.innerText || '');
      utter.lang = document.documentElement.lang || 'ar';
      utter.rate = currentRate();
      try{ synth.speak(utter); toast('بدء الاستماع'); }catch(e){ toast('تعذر تشغيل الاستماع'); }
    });

    on(stopBtn,'click', function(){ stop(); toast('تم الإيقاف'); });

    on(rateInp,'input', function(){
      if(utter){ utter.rate = currentRate(); }
    });

    on(dlBtn,'click', function(){
      var id = getNewsId();
      if(!id){ toast('تعذر تحديد رقم الخبر'); return; }
      var lang = document.documentElement.lang || 'ar';
      var rate = currentRate();
      window.location.href = '/api/news/tts?id=' + encodeURIComponent(id) + '&lang=' + encodeURIComponent(lang) + '&format=mp3&download=1&rate=' + encodeURIComponent(rate);
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    initReportTools();
    initTTS();
  });
})();  function getNewsId(){
    var bm = document.getElementById('gdyBookmark');
    if(bm && bm.dataset && bm.dataset.newsId) return parseInt(bm.dataset.newsId,10)||0;
    var tts = document.getElementById('gdy-tts');
    if(tts && tts.dataset && tts.dataset.newsId) return parseInt(tts.dataset.newsId,10)||0;
    var poll = document.getElementById('gdy-poll');
    if(poll && poll.dataset && poll.dataset.newsId) return parseInt(poll.dataset.newsId,10)||0;
    return 0;
  }

