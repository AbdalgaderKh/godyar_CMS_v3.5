document.addEventListener('DOMContentLoaded', function () {
  if (window.GDYEditorPro && typeof window.GDYEditorPro.initAll === 'function') {
    window.GDYEditorPro.initAll(document);
  }

  var form = document.getElementById('news-editor-form');
  var title = document.getElementById('title');
  var slug = document.getElementById('slug');
  var content = document.getElementById('content');
  var status = document.getElementById('gdyAutosaveStatus');

  if (!form || !title || !content) return;

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content') || '';
    var input = document.getElementById('gdyGlobalCsrfToken');
    return input ? (input.value || '') : '';
  }

  var timer = null;
  function autosave() {
    var fd = new FormData();
    fd.append('title', title.value || '');
    fd.append('slug', slug ? slug.value : '');
    fd.append('content', content.value || '');
    fd.append('_token', getCsrfToken());

    fetch('autosave.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: {
        'X-CSRF-Token': getCsrfToken()
      }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (status && data && data.ok) {
        status.textContent = 'الحفظ التلقائي: ' + (data.saved_at || 'تم');
      }
    })
    .catch(function () {
      if (status) status.textContent = 'الحفظ التلقائي: فشل مؤقت';
    });
  }

  function queue() {
    clearTimeout(timer);
    timer = setTimeout(autosave, 20000);
  }

  title.addEventListener('input', queue);
  content.addEventListener('input', queue);
  if (slug) slug.addEventListener('input', queue);
});