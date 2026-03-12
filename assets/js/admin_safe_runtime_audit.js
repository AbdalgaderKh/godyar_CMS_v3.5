document.addEventListener('DOMContentLoaded', function () {
  var links = document.querySelectorAll('.actions a');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function () {
      console.log('Admin-safe runtime audit started.');
    });
  }
});
