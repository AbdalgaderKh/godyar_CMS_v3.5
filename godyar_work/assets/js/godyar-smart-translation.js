document.addEventListener('DOMContentLoaded', function () {
  var source = document.querySelector('[data-smart-translation-source]');
  var preview = document.querySelector('[data-smart-translation-preview]');
  if (!source || !preview) return;
  source.addEventListener('input', function () {
    preview.textContent = source.value.length ? source.value : '...';
  });
});
