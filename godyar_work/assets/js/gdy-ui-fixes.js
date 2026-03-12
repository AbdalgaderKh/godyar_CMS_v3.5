
(function(){
  function qs(sel){ return document.querySelector(sel); }
  function qsa(sel){ return Array.prototype.slice.call(document.querySelectorAll(sel)); }

  function getThemeClass(el){
    if(!el) return "";
    var m = (el.className||"").match(/\btheme-[a-z0-9_-]+\b/i);
    return m ? m[0] : "";
  }

  function setThemeClass(el, cls){
    if(!el || !cls) return;
    el.className = (el.className||"").replace(/\btheme-[a-z0-9_-]+\b/ig, "").replace(/\s+/g," ").trim();
    el.classList.add(cls);
  }

  function getPrimaryRGB(){
    try{
      var v = getComputedStyle(document.documentElement).getPropertyValue("--primary-rgb").trim();
      if(v) return v;
    }catch(e){}
    return "";
  }

  function setMetaThemeColorFromCSS(){
    try{
      var rgb = getPrimaryRGB();
      if(!rgb) return;
      var parts = rgb.split(",").map(function(x){ return parseInt(x.trim(),10); });
      if(parts.length !== 3 || parts.some(isNaN)) return;
      var hex = "#" + parts.map(function(n){
        var s = n.toString(16);
        return s.length===1 ? ("0"+s) : s;
      }).join("");
      var meta = qs('meta[name="theme-color"]');
      if(meta) meta.setAttribute("content", hex);
    }catch(e){}
  }

  function setMode(mode){
    try{
      var html = document.documentElement;
      html.setAttribute("data-theme", mode);
      localStorage.setItem("gdy_theme_mode", mode);

      var btn = qs("[data-gdy-theme-toggle]") || qs("#gdyThemeToggle");
      if(btn){
        btn.setAttribute("aria-pressed", mode === "dark" ? "true" : "false");
        btn.title = (mode === "dark") ? "الوضع النهاري" : "الوضع الليلي";
      }
    }catch(e){}
  }

  function initMode(){
    try{
      var stored = localStorage.getItem("gdy_theme_mode");
      if(stored === "light" || stored === "dark"){
        setMode(stored);
        return;
      }
      var cur = document.documentElement.getAttribute("data-theme");
      if(cur === "light" || cur === "dark"){
        setMode(cur);
        return;
      }
      var prefersDark = false;
      try{ prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches; }catch(e){}
      setMode(prefersDark ? "dark" : "light");
    }catch(e){}
  }

  function toggleMode(){
    var cur = document.documentElement.getAttribute("data-theme") || "light";
    setMode(cur === "dark" ? "light" : "dark");
  }

  function wireModeToggle(){
    document.addEventListener("click", function(ev){
      var t = ev.target;
      if(!t) return;
      var btn = t.closest ? t.closest("[data-gdy-theme-toggle],#gdyThemeToggle") : null;
      if(btn){
        ev.preventDefault();
        toggleMode();
      }
    }, true);
  }

  function persistTheme(){
    try{
      var body = document.body;
      var current = getThemeClass(body);
      var stored = localStorage.getItem("gdy_theme_front") || "";

      if (stored) {
        setThemeClass(body, stored);
        current = stored;
      } else if (current) {
        localStorage.setItem("gdy_theme_front", current);
      }

      try {
        if (current) {
          var t = (current + "").replace(/^theme-/, "");
          document.cookie = "gdy_theme_front=" + encodeURIComponent(t) + "; path=/; max-age=31536000; samesite=lax";
        }
      } catch (e) {}
    }catch(e){}
  }

  function wireLangLinks(){
    try{
      var currentTheme = getThemeClass(document.body);
      var currentMode = (document.documentElement.getAttribute("data-theme") || "light");
      qsa("#gdyLangDd a[href]").forEach(function(a){
        a.addEventListener("click", function(){
          try{
            if(currentTheme) localStorage.setItem("gdy_theme_front", currentTheme);
            localStorage.setItem("gdy_theme_mode", currentMode);
          }catch(e){}
        }, {passive:true});
      });
    }catch(e){}
  }

  function pushToast(){
    var toast = document.getElementById("gdy-push-toast");
    if(!toast) return;

    function hide(){ toast.classList.add("hidden"); }

    try{
      if(localStorage.getItem("gdy_push_optout") === "1"){
        hide(); return;
      }
    }catch(e){}

    var btnEnable = toast.querySelector("[data-gdy-push-enable]");
    var btnLater  = toast.querySelector("[data-gdy-push-later]");

    if(btnLater){
      btnLater.addEventListener("click", function(){
        try{ localStorage.setItem("gdy_push_optout","1"); }catch(e){}
        hide();
      });
    }

    if(btnEnable){
      btnEnable.addEventListener("click", async function(){
        try{
          if(!("Notification" in window)){ hide(); return; }
          var perm = Notification.permission;
          if(perm === "default"){
            perm = await Notification.requestPermission();
          }
          hide();
          if(perm === "granted"){
            try{ localStorage.removeItem("gdy_push_optout"); }catch(e){}
          }
        }catch(e){ hide(); }
      });
    }
  }

  function boot(){
    initMode();
    wireModeToggle();
    persistTheme();
    wireLangLinks();
    setMetaThemeColorFromCSS();
    pushToast();
  }

  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", boot);
  }else{
    boot();
  }
})();
