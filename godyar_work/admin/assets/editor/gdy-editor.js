(function () {
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

  document.addEventListener('paste', function (e) {
    var target = e.target;
    if (!target || !target.closest('.gdy-editor-surface')) return;

    var clipboard = e.clipboardData || window.clipboardData;
    if (!clipboard) return;

    var html = clipboard.getData('text/html');
    var text = clipboard.getData('text/plain');

    if (html) {
      e.preventDefault();
      document.execCommand('insertHTML', false, cleanWordHtml(html));
      return;
    }

    if (text) {
      e.preventDefault();
      var escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\n/g, '<br>');
      document.execCommand('insertHTML', false, escaped);
    }
  });
})();