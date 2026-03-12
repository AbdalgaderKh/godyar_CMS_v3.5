(function(){
  function qs(id){ return document.getElementById(id); }
  function plain(html){ var d=document.createElement('div'); d.innerHTML=String(html||''); return (d.textContent||d.innerText||'').replace(/\s+/g,' ').trim(); }
  function words(text){ if (!text) return 0; return text.split(/\s+/).filter(Boolean).length; }
  function readingTime(count){ return Math.max(1, Math.ceil(count/220)); }
  function escapeHtml(s){ return String(s||'').replace(/[&<>\"]/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c] || c; }); }
  function headlineScore(title){
    var score = 0, len = (title || '').trim().length, wordsCount=(title||'').split(/\s+/).filter(Boolean).length;
    if (len >= 25 && len <= 70) score += 40;
    if (wordsCount >= 5) score += 20;
    if (/\d/.test(title)) score += 10;
    if (/[:!؟!?،]/.test(title)) score += 10;
    if (!/\.{3}|…/.test(title)) score += 10;
    if (!/(عاجل|حصري|صادم|لن|شاهد|كارثة)/.test(title)) score += 10;
    return { score: score, label: score >= 75 ? 'قوي' : (score >= 55 ? 'جيد' : 'يحتاج تحسين') };
  }
  function debounce(fn, delay){ var t; return function(){ var args=arguments, self=this; clearTimeout(t); t=setTimeout(function(){ fn.apply(self,args); }, delay || 200); }; }
  document.addEventListener('DOMContentLoaded', function(){
    var form = qs('news-editor-form');
    var titleEl = qs('title');
    var slugEl = qs('slug');
    var contentEl = qs('content');
    var tagsEl = qs('tags');
    var seoTitleEl = qs('seo_title');
    var seoDescEl = qs('seo_description');
    var excerptEl = qs('excerpt');
    if (!form || !titleEl || !contentEl) return;
    var autosaveUrl = form.getAttribute('data-autosave-url') || 'autosave.php';
    var newsId = form.getAttribute('data-news-id') || '0';
    var latestAutosave = null;

    function currentContent(){ return contentEl.value !== undefined ? contentEl.value : (contentEl.innerHTML || ''); }
    function getQuerySeed(){
      var selected = '';
      try { selected = window.getSelection ? window.getSelection().toString().trim() : ''; } catch(e) {}
      if (selected) return selected;
      if (titleEl.value.trim()) return titleEl.value.trim().split(/\s+/).slice(0, 6).join(' ');
      return plain(currentContent()).split(/\s+/).slice(0, 8).join(' ');
    }
    function ensurePanel(){
      if (qs('gdyEditorInsightWrap')) return qs('gdyEditorInsightWrap');
      var target = document.querySelector('.col-md-4');
      if (!target) return null;
      var wrap = document.createElement('div');
      wrap.id='gdyEditorInsightWrap';
      wrap.className='gdy-editor-grid mb-3';
      wrap.innerHTML=''
        + '<div class="gdy-restore-banner d-none" id="gdyRestoreBanner"><div><strong>تم العثور على حفظ تلقائي أحدث.</strong><div class="gdy-editor-help" id="gdyRestoreMeta">يمكنك استعادته أو تجاهله.</div></div><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-info" id="gdyRestoreDraftBtn">استعادة</button><button type="button" class="btn btn-sm btn-outline-light" id="gdyDismissRestoreBtn">تجاهل</button></div></div>'
        + '<div class="gdy-editor-metrics">'
        + ' <div class="gdy-metric"><div class="gdy-metric-label">عدد الكلمات</div><div class="gdy-metric-value" id="gdyMetricWords">0</div></div>'
        + ' <div class="gdy-metric"><div class="gdy-metric-label">وقت القراءة</div><div class="gdy-metric-value" id="gdyMetricRead">1 دقيقة</div></div>'
        + ' <div class="gdy-metric"><div class="gdy-metric-label">طول العنوان</div><div class="gdy-metric-value" id="gdyMetricTitle">0</div></div>'
        + ' <div class="gdy-metric"><div class="gdy-metric-label">وصف SEO</div><div class="gdy-metric-value" id="gdyMetricDesc">0</div></div>'
        + '</div>'
        + '<div class="gdy-workflow-card">'
        + ' <div class="gdy-seo-label">سير العمل</div>'
        + ' <div class="gdy-workflow-line"><span class="gdy-workflow-pill" id="gdyWorkflowStatus">مسودة</span><span class="gdy-workflow-pill gdy-muted-pill" id="gdyWorkflowSchedule">غير مجدول</span><span class="gdy-workflow-pill gdy-muted-pill" id="gdyWorkflowAutosave">لم يتم الحفظ بعد</span></div>'
        + ' <div class="gdy-editor-help" id="gdyWorkflowHint">اختر حالة المقال وتاريخ النشر لتوضيح مسار النشر.</div>'
        + '</div>'
        + '<div class="gdy-seo-preview-card">'
        + ' <div class="gdy-seo-label">معاينة محرك البحث</div>'
        + ' <div class="gdy-serp-url" id="gdySerpUrl"></div>'
        + ' <div class="gdy-serp-title" id="gdySerpTitle"></div>'
        + ' <div class="gdy-serp-desc" id="gdySerpDesc"></div>'
        + ' <ul class="gdy-checklist mt-3" id="gdyChecklist"></ul>'
        + '</div>'
        + '<div class="gdy-link-suggestions-card">'
        + ' <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">'
        + '   <div><div class="gdy-seo-label mb-0">اقتراح روابط داخلية</div><div class="gdy-editor-help">يعتمد على النص المحدد أو عنوان المقال</div></div>'
        + '   <span class="gdy-link-query-badge" id="gdyLinkSeed">جاهز</span>'
        + ' </div>'
        + ' <div class="results mt-2" id="gdyLinksSuggestResults"></div>'
        + '</div>';
      target.prepend(wrap);
      return wrap;
    }
    function setText(id,val){ var el=qs(id); if (el) el.textContent = val; }
    function getStatusValue(){ var el=form.querySelector('[name="status"]'); return el ? String(el.value || 'draft') : 'draft'; }
    function getScheduleValue(){
      var field = form.querySelector('[data-schedule-field="1"]') || form.querySelector('[name="publish_at"]') || form.querySelector('[name="published_at"]');
      return field ? String(field.value || '') : '';
    }
    function updateWorkflow(){
      var statusMap = { draft:'مسودة', pending:'بانتظار المراجعة', approved:'معتمد', published:'منشور', archived:'مؤرشف' };
      var status = getStatusValue();
      var schedule = getScheduleValue();
      var statusEl = qs('gdyWorkflowStatus');
      var scheduleEl = qs('gdyWorkflowSchedule');
      var hintEl = qs('gdyWorkflowHint');
      if (statusEl) statusEl.textContent = statusMap[status] || status;
      if (scheduleEl) scheduleEl.textContent = schedule ? ('مجدول: ' + schedule.replace('T',' ')) : 'غير مجدول';
      if (hintEl) {
        if (status === 'published' && !schedule) hintEl.textContent = 'هذا المقال سيظهر فورًا بعد الحفظ.';
        else if (schedule) hintEl.textContent = 'تم تحديد وقت نشر. راجع المنطقة الزمنية قبل الحفظ.';
        else if (status === 'pending') hintEl.textContent = 'المقال ينتظر المراجعة والاعتماد.';
        else hintEl.textContent = 'يمكنك حفظ المقال كمسودة أو جدولته أو إرساله للمراجعة.';
      }
      var scheduleStatus = qs('gdyScheduleStatus');
      if (scheduleStatus) scheduleStatus.textContent = schedule ? ('الجدولة: ' + schedule.replace('T', ' ')) : 'الجدولة: غير محددة';
    }
    function update(){
      ensurePanel();
      var title = titleEl.value.trim();
      var seoTitle = (seoTitleEl && seoTitleEl.value.trim()) || title;
      var text = plain(currentContent());
      var desc = (seoDescEl && seoDescEl.value.trim()) || text.slice(0, 160);
      var wc = words(text);
      setText('gdyMetricWords', String(wc));
      setText('gdyMetricRead', readingTime(wc) + ' دقيقة');
      setText('gdyMetricTitle', String(title.length));
      setText('gdyMetricDesc', String(desc.length));
      setText('gdySerpTitle', seoTitle || 'عنوان المقال');
      setText('gdySerpDesc', desc || 'سيظهر وصف المقال هنا بعد إدخال المحتوى أو وصف SEO.');
      setText('gdySerpUrl', (window.GODYAR_BASE_URL || location.origin).replace(/\/$/, '') + '/news/' + ((slugEl && slugEl.value.trim()) || 'example-slug'));
      var checklist = qs('gdyChecklist');
      if (checklist){
        var items = [
          ['العنوان مناسب', title.length >= 20 && title.length <= 70],
          ['وصف SEO مناسب', desc.length >= 80 && desc.length <= 170],
          ['المقال غني بالمحتوى', wc >= 150],
          ['الوسوم موجودة', !!(tagsEl && tagsEl.value.trim())],
          ['المقدمة متوفرة', !!(excerptEl && excerptEl.value.trim())]
        ];
        checklist.innerHTML = items.map(function(item){ var cls=item[1] ? 'gdy-ok' : 'gdy-warn'; return '<li><strong>'+item[0]+'</strong><span class="gdy-check-badge '+cls+'">'+(item[1]?'جيد':'يحتاج تحسين')+'</span></li>'; }).join('');
      }
      var hs = headlineScore(title), hsEl = qs('gdyHeadlineScore');
      if (hsEl) hsEl.textContent = 'تحليل العنوان: ' + hs.score + '/100 — ' + hs.label;
      setText('gdyLinkSeed', getQuerySeed().slice(0, 40) || 'بدون عبارة');
      updateWorkflow();
    }
    function applyPayload(payload){
      if (!payload) return;
      ['title','slug','excerpt','content','seo_title','seo_description','tags','status','publish_at','published_at'].forEach(function(name){
        var el = form.querySelector('[name="'+name+'"]');
        var source = name === 'published_at' ? (payload.scheduled_at || payload.publish_at || '') : (payload[name] || '');
        if (el && source) el.value = source;
      });
      update();
    }
    async function checkAutosaveRestore(){
      ensurePanel();
      var banner = qs('gdyRestoreBanner');
      var meta = qs('gdyRestoreMeta');
      if (!banner) return;
      try{
        var res = await fetch(autosaveUrl + '?news_id=' + encodeURIComponent(newsId), { credentials:'same-origin' });
        var data = await res.json();
        if (!data || !data.ok || !data.found || !data.payload) return;
        latestAutosave = data.payload;
        var savedAt = data.saved_at || data.payload.updated_at || '';
        if (meta) meta.textContent = savedAt ? ('آخر حفظ تلقائي: ' + savedAt.replace('T', ' ').replace('Z','')) : 'يمكنك استعادة آخر مسودة محفوظة.';
        banner.classList.remove('d-none');
      }catch(e){}
    }
    async function autosave(){
      var statusEl = qs('gdyAutosaveStatus');
      var workflowAuto = qs('gdyWorkflowAutosave');
      var fd = new FormData();
      ['title','slug','excerpt','content','seo_title','seo_description','tags','status','publish_at','published_at'].forEach(function(name){ var el=form.querySelector('[name="'+name+'"]'); if (el) fd.append(name, el.value || ''); });
      fd.append('news_id', newsId || '0');
      var csrfMeta = document.querySelector('meta[name="csrf-token"]');
      if (csrfMeta) fd.append('_token', csrfMeta.getAttribute('content') || '');
      try{
        if (statusEl) statusEl.textContent = 'الحفظ التلقائي: جارٍ الحفظ...';
        if (workflowAuto) workflowAuto.textContent = 'يتم الحفظ الآن';
        var res = await fetch(autosaveUrl, { method:'POST', body:fd, credentials:'same-origin' });
        var data = await res.json();
        if (data && data.ok) {
          var msg = 'الحفظ التلقائي: تم الحفظ ' + (data.saved_at || '');
          if (statusEl) statusEl.textContent = msg;
          if (workflowAuto) workflowAuto.textContent = 'آخر حفظ: ' + (data.saved_at || 'الآن');
        } else {
          if (statusEl) statusEl.textContent = 'الحفظ التلقائي: تعذر الحفظ';
          if (workflowAuto) workflowAuto.textContent = 'تعذر الحفظ';
        }
      } catch(e){
        if (statusEl) statusEl.textContent = 'الحفظ التلقائي: تعذر الحفظ';
        if (workflowAuto) workflowAuto.textContent = 'تعذر الحفظ';
      }
    }
    async function suggestLinks(){
      var out = qs('gdyLinksSuggestResults') || qs('internal-links-results');
      if (!out) return;
      var qInput = qs('internal-link-query');
      var seed = (qInput && qInput.value.trim()) || getQuerySeed();
      if (!seed){ out.innerHTML = '<div class="gdy-editor-help">أدخل عنوانًا أو حدد نصًا من المحتوى أولاً.</div>'; return; }
      out.innerHTML = '<div class="gdy-editor-help">جارٍ جلب الاقتراحات…</div>';
      try{
        var res = await fetch('ajax_internal_links.php?q=' + encodeURIComponent(seed), { credentials:'same-origin' });
        var data = await res.json();
        if (!data.ok || !Array.isArray(data.items) || !data.items.length){ out.innerHTML = '<div class="gdy-editor-help">لا توجد اقتراحات مناسبة لهذه العبارة.</div>'; return; }
        out.innerHTML = data.items.map(function(item){
          return '<div class="gdy-link-row"><a href="'+item.url+'" target="_blank" rel="noopener"><strong>'+escapeHtml(item.title)+'</strong><span class="meta">'+escapeHtml(item.meta || item.url)+'</span></a><button type="button" class="btn btn-sm btn-outline-info gdy-insert-link" data-url="'+escapeHtml(item.url)+'" data-title="'+escapeHtml(item.title)+'">إدراج</button></div>';
        }).join('');
      }catch(e){ out.innerHTML = '<div class="gdy-editor-help">تعذر تحميل الاقتراحات الآن.</div>'; }
    }
    function insertInternalLink(url, title){
      if (!contentEl) return;
      var selected='';
      try{ selected = window.getSelection ? window.getSelection().toString().trim() : ''; }catch(e){}
      var text = selected || title || url;
      var link = '<a href="' + url + '">' + text + '</a>';
      if (typeof contentEl.setRangeText === 'function') {
        var start = contentEl.selectionStart || 0, end = contentEl.selectionEnd || 0;
        contentEl.setRangeText(link, start, end, 'end');
      } else {
        contentEl.value += '\n' + link;
      }
      update();
    }
    function renderDiffText(text, changed){
      if (!text) return '—';
      return changed ? ('<div class="gdy-diff-snippet">' + escapeHtml(text) + '</div>') : escapeHtml(text);
    }
    async function compareRevisions(){
      var panel = qs('gdyRevisionComparePanel'), left = qs('gdyCompareLeft'), right = qs('gdyCompareRight');
      if (!panel || !left || !left.value) return;
      panel.classList.remove('d-none');
      panel.innerHTML = '<div class="gdy-editor-help">جارٍ تحميل المقارنة…</div>';
      try{
        var url = (form.getAttribute('data-compare-url') || 'ajax_revision_compare.php') + '?news_id=' + encodeURIComponent(newsId || '0') + '&left=' + encodeURIComponent(left.value) + '&right=' + encodeURIComponent((right && right.value) || '0');
        var res = await fetch(url, { credentials:'same-origin' });
        var data = await res.json();
        if (!data.ok || !Array.isArray(data.rows)) throw new Error('bad_response');
        var meta = data.meta || {};
        var header = '<div class="gdy-compare-header"><div><strong>الفروقات المكتشفة:</strong> ' + (meta.changed_count || 0) + '</div><div class="small text-secondary">يسار: ' + escapeHtml(((meta.left||{}).label || 'نسخة')) + ' — يمين: ' + escapeHtml(((meta.right||{}).label || 'الحالية')) + '</div></div>';
        panel.innerHTML = header + data.rows.map(function(r){
          return '<div class="gdy-compare-row ' + (r.changed ? 'is-changed' : '') + '"><div class="gdy-compare-label">'+escapeHtml(r.label)+'</div><div class="gdy-compare-col"><strong>النسخة</strong><div>'+renderDiffText(r.left || '', !!r.changed)+'</div><span class="gdy-compare-meta">'+(r.left_length || 0)+' حرف</span></div><div class="gdy-compare-col"><strong>الحالية</strong><div>'+renderDiffText(r.right || '', !!r.changed)+'</div><span class="gdy-compare-meta">'+(r.right_length || 0)+' حرف</span></div></div>';
        }).join('');
      }catch(e){ panel.innerHTML = '<div class="gdy-editor-help">تعذر تحميل المقارنة الآن.</div>'; }
    }
    function sanitizePaste(e){
      var text = '';
      try { text = (e.clipboardData || window.clipboardData).getData('text/plain') || ''; } catch(err) {}
      if (!text) return;
      e.preventDefault();
      var normalized = text.replace(/\u00A0/g, ' ').replace(/\r\n/g, '\n').replace(/\n{3,}/g, '\n\n').replace(/[\t ]{2,}/g, ' ');
      if (document.execCommand) document.execCommand('insertText', false, normalized);
      else if (typeof contentEl.setRangeText === 'function') {
        var start = contentEl.selectionStart || 0, end = contentEl.selectionEnd || 0;
        contentEl.setRangeText(normalized, start, end, 'end');
      } else {
        contentEl.value += normalized;
      }
      update();
    }

    ensurePanel();
    update();
    checkAutosaveRestore();

    var watchFields = [titleEl, seoTitleEl, seoDescEl, tagsEl, slugEl, contentEl, excerptEl, form.querySelector('[name="status"]'), form.querySelector('[name="publish_at"]'), form.querySelector('[name="published_at"]')].filter(Boolean);
    ['input','change'].forEach(function(evt){ watchFields.forEach(function(el){ el.addEventListener(evt, update); }); });
    var btn = qs('btn-suggest-links'); if (btn) btn.addEventListener('click', suggestLinks);
    var qInput = qs('internal-link-query'); if (qInput) qInput.addEventListener('keydown', function(e){ if (e.key === 'Enter'){ e.preventDefault(); suggestLinks(); } });
    if (contentEl) contentEl.addEventListener('paste', sanitizePaste);
    document.addEventListener('click', function(e){
      var insertBtn = e.target.closest('.gdy-insert-link');
      if (insertBtn) { e.preventDefault(); insertInternalLink(insertBtn.getAttribute('data-url') || '', insertBtn.getAttribute('data-title') || ''); }
      if (e.target && e.target.id === 'gdyRestoreDraftBtn' && latestAutosave) { applyPayload(latestAutosave); qs('gdyRestoreBanner').classList.add('d-none'); }
      if (e.target && e.target.id === 'gdyDismissRestoreBtn') { qs('gdyRestoreBanner').classList.add('d-none'); }
    });
    var compareBtn = qs('gdyCompareBtn'); if (compareBtn) compareBtn.addEventListener('click', compareRevisions);
    var saveTimer = null;
    var debouncedAutosave = debounce(function(){ autosave(); }, 1500);
    ['input','change'].forEach(function(evt){ watchFields.forEach(function(el){ el.addEventListener(evt, function(){ clearTimeout(saveTimer); debouncedAutosave(); }); }); });
    setInterval(update, 5000);
    setInterval(autosave, 30000);
  });
})();
