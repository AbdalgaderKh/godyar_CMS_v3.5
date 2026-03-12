document.addEventListener('DOMContentLoaded', function () {
  var links = document.querySelectorAll('[data-run-audit]');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function () {
      console.log('Runtime translation audit trigger clicked.');
    });
  }
});
