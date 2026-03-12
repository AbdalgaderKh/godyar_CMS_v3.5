(function () {
  if (window.GDYEditorPro) return;

  function cleanWordHtml(html) {
    if (!html) return '';

    return html
      .replace(/<!--[\s\S]*?-->/g, '')
      .replace(/<o:p>\s*<\/o:p>/gi, '')
      .replace(/<o:p>[\s\S]*?<\/o:p>/gi, '')
      .replace(/\s*mso-[^:"]+:[^;"]*;?/gi, '')
      .replace(/\s*class=("|\')?Mso[^"\']*("|\')?/gi, '')
      .replace(/\s*style=("|\')(.*?)\1/gi, function (_, q, style) {
        var cleaned = style
          .replace(/mso-[^:]+:[^;]+;?/gi, '')
          .replace(/font-family:[^;]+;?/gi, '')
          .replace(/line-height:[^;]+;?/gi, '')
          .replace(/margin-top:[^;]+;?/gi, '')
          .replace(/margin-bottom:[^;]+;?/gi, '')
          .trim();

        return cleaned ? ' style="' + cleaned + '"' : '';
      });
  }

  function exec(cmd, value) {
    try {
      document.execCommand(cmd, false, value || null);
    } catch (e) {}
  }

  function syncTextarea(textarea, editor) {
    textarea.value = editor.innerHTML;
  }

  function buildButton(label, cmd, title) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gdy-editor-btn';
    btn.setAttribute('data-cmd', cmd);
    btn.setAttribute('title', title || label);
    btn.innerHTML = label;
    return btn;
  }

  function buildToolbar() {
    var toolbar = document.createElement('div');
    toolbar.className = 'gdy-editor-toolbar';

    var buttons = [
      ['B', 'bold', 'عريض'],
      ['I', 'italic', 'مائل'],
      ['U', 'underline', 'تحته خط'],
      ['•', 'insertUnorderedList', 'قائمة نقطية'],
      ['1.', 'insertOrderedList', 'قائمة رقمية'],
      ['❝', 'formatBlock', 'اقتباس', 'blockquote'],
      ['↶', 'undo', 'تراجع'],
      ['↷', 'redo', 'إعادة'],
      ['⌫', 'removeFormat', 'إزالة التنسيق'],
      ['🔗', 'createLink', 'رابط'],
      ['🖼', 'insertImage', 'صورة'],
      ['—', 'insertHorizontalRule', 'فاصل'],
    ];

    buttons.forEach(function (item) {
      var btn = buildButton(item[0], item[1], item[2]);
      if (item[3]) btn.setAttribute('data-value', item[3]);
      toolbar.appendChild(btn);
    });

    var select = document.createElement('select');
    select.className = 'gdy-editor-select';
    select.innerHTML =
      '<option value="">نمط النص</option>' +
      '<option value="p">فقرة</option>' +
      '<option value="h1">عنوان 1</option>' +
      '<option value="h2">عنوان 2</option>' +
      '<option value="h3">عنوان 3</option>' +
      '<option value="h4">عنوان 4</option>';
    toolbar.appendChild(select);

    select.addEventListener('change', function () {
      if (!select.value) return;
      exec('formatBlock', select.value);
      select.value = '';
    });

    toolbar.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-cmd]');
      if (!btn) return;

      var cmd = btn.getAttribute('data-cmd');
      var value = btn.getAttribute('data-value') || null;

      if (cmd === 'createLink') {
        value = window.prompt('أدخل الرابط', 'https://');
        if (!value) return;
      }

      if (cmd === 'insertImage') {
        value = window.prompt('أدخل رابط الصورة', 'https://');
        if (!value) return;
      }

      exec(cmd, value);
    });

    return toolbar;
  }

  function initOne(textarea) {
    if (!textarea || textarea.dataset.gdyEditorReady === '1') return;
    textarea.dataset.gdyEditorReady = '1';

    var wrap = document.createElement('div');
    wrap.className = 'gdy-editor-wrap';

    var toolbar = buildToolbar();
    var editor = document.createElement('div');
    editor.className = 'gdy-editor-area';
    editor.contentEditable = 'true';
    editor.setAttribute('dir', 'rtl');
    editor.setAttribute('data-placeholder', textarea.getAttribute('placeholder') || 'ابدأ الكتابة هنا...');
    editor.innerHTML = textarea.value || '';

    textarea.style.display = 'none';
    textarea.parentNode.insertBefore(wrap, textarea);
    wrap.appendChild(toolbar);
    wrap.appendChild(editor);
    wrap.appendChild(textarea);

    editor.addEventListener('input', function () {
      syncTextarea(textarea, editor);
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
    });

    editor.addEventListener('paste', function (e) {
      var clipboard = e.clipboardData || window.clipboardData;
      if (!clipboard) return;

      var html = clipboard.getData('text/html');
      var text = clipboard.getData('text/plain');

      if (html) {
        e.preventDefault();
        exec('insertHTML', cleanWordHtml(html));
        syncTextarea(textarea, editor);
        return;
      }

      if (text) {
        e.preventDefault();
        var escaped = text
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/\n/g, '<br>');
        exec('insertHTML', escaped);
        syncTextarea(textarea, editor);
      }
    });

    var form = textarea.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        syncTextarea(textarea, editor);
      });
    }
  }

  window.GDYEditorPro = {
    initAll: function (root) {
      var scope = root || document;
      var areas = scope.querySelectorAll('textarea[data-gdy-editor="1"]');
      areas.forEach(initOne);
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      window.GDYEditorPro.initAll(document);
    });
  } else {
    window.GDYEditorPro.initAll(document);
  }
})();