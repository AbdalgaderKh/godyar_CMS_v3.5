(function(){
  function applyTheme(t){
    var theme = (t === 'dark') ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', theme);
    document.body.classList.toggle('theme-dark', theme === 'dark');
    try{ localStorage.setItem('gdy_theme', theme); }catch(e){}

    var btn = document.getElementById('gdyThemeToggle');
    if (btn) btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
  }

  function currentTheme(){
    var t = null;
    try{ t = localStorage.getItem('gdy_theme'); }catch(e){}
    if (t) return t;
    var attr = document.documentElement.getAttribute('data-theme');
    if (attr) return attr;
    return 'light';
  }

  function toggleTheme(){
    applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
  }

  document.addEventListener('DOMContentLoaded', function(){
    applyTheme(currentTheme());

    var btn = document.getElementById('gdyThemeToggle');
    if (btn) btn.addEventListener('click', toggleTheme);

    var mobileThemeBtn = document.querySelector('.gdy-mobile-bar button[data-action="theme"]');
    if (mobileThemeBtn) mobileThemeBtn.addEventListener('click', toggleTheme);
  });
})();
