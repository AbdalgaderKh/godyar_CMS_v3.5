document.addEventListener('DOMContentLoaded', function () {
  var title = document.getElementById('title');
  var content = document.getElementById('content');
  var seoTitle = document.getElementById('seo_title');
  var seoDescription = document.getElementById('seo_description');
  var tagsHidden = document.getElementById('tags-hidden') || document.querySelector('input[name="tags"]');

  if (!title) return;

  var box = document.getElementById('gdySeoAnalyzerBox');
  if (!box) {
    box = document.createElement('div');
    box.id = 'gdySeoAnalyzerBox';
    box.style.cssText = 'margin-top:12px;padding:12px;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:rgba(2,6,23,.2);color:#e5e7eb;';
    box.innerHTML =
      '<div style="font-weight:800;margin-bottom:8px">SEO Auto Analyzer</div>' +
      '<div id="gdySeoScore" style="font-size:1.1rem;font-weight:800;margin-bottom:6px">النتيجة: —</div>' +
      '<ul id="gdySeoTips" style="margin:0;padding-right:18px;line-height:1.8"></ul>';
    var target = document.querySelector('#gdy-serp-preview-card .gdy-card-body') || document.querySelector('.gdy-card-body');
    if (target) target.appendChild(box);
  }

  function textFromHtml(html) {
    var div = document.createElement('div');
    div.innerHTML = html || '';
    return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
  }

  function topWords(text, max) {
    text = (text || '').toLowerCase().replace(/[^\u0600-\u06FFa-z0-9\s]/gi, ' ');
    var words = text.split(/\s+/).filter(Boolean);
    var stop = new Set(['في','على','من','إلى','عن','هذا','هذه','ذلك','تلك','و','أو','ثم','كما','مع','ما','لم','لن','قد','هو','هي','هم','هن','the','and','or','to','of','in','on','for','a','an']);
    var freq = new Map();
    words.forEach(function (w) {
      if (w.length < 3 || stop.has(w)) return;
      freq.set(w, (freq.get(w) || 0) + 1);
    });
    return Array.from(freq.entries()).sort(function (a,b){ return b[1]-a[1]; }).slice(0, max).map(function (x){ return x[0]; });
  }

  function analyze() {
    var score = 0;
    var tips = [];

    var t = (title.value || '').trim();
    var c = textFromHtml(content ? content.value : '');
    var st = seoTitle ? seoTitle.value.trim() : '';
    var sd = seoDescription ? seoDescription.value.trim() : '';
    var tg = tagsHidden ? tagsHidden.value.trim() : '';

    if (t.length >= 20 && t.length <= 70) score += 30; else tips.push('العنوان الأفضل بين 20 و70 حرفًا.');
    if (c.length >= 300) score += 25; else tips.push('المحتوى ما زال قصيرًا نسبيًا.');
    if (st.length >= 20 && st.length <= 60) score += 20; else tips.push('SEO Title يحتاج ضبطًا.');
    if (sd.length >= 80 && sd.length <= 160) score += 15; else tips.push('SEO Description الأفضل بين 80 و160 حرفًا.');
    if (tg.length >= 3) score += 10; else tips.push('أضف وسومًا مناسبة.');

    var scoreEl = document.getElementById('gdySeoScore');
    var tipsEl = document.getElementById('gdySeoTips');
    if (scoreEl) scoreEl.textContent = 'النتيجة: ' + score + '/100';
    if (tipsEl) {
      tipsEl.innerHTML = '';
      if (!tips.length) {
        tipsEl.innerHTML = '<li>ممتاز — الإعدادات الأساسية جيدة.</li>';
      } else {
        tips.forEach(function (tip) {
          var li = document.createElement('li');
          li.textContent = tip;
          tipsEl.appendChild(li);
        });
      }
    }

    if (tagsHidden && !tagsHidden.value.trim()) {
      var suggested = topWords(t + ' ' + c, 6);
      if (suggested.length) tagsHidden.value = suggested.join(', ');
    }
  }

  [title, content, seoTitle, seoDescription, tagsHidden].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', analyze);
  });

  analyze();
});