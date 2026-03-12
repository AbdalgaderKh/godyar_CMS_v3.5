  function getBaseUrl() {
    var b = (window.GDY_BASE_URL || "").trim();
(function () {

  function getBaseUrl() {
    let b = (window.GDY_BASE_URL || "").trim();
    if (b) return b.replace(/\/+$/, "");
    const body = document.body;
    if (body?.dataset?.baseUrl) {
      b = (body?.dataset?.baseUrl || "").trim();
      if (b) return b.replace(/\/+$/, "");
    function renderPreview(card) {
    var url = card.getAttribute("data-file-url") || card.getAttribute("data-url") || "";
    var host = card.querySelector(".gdy-attach-preview");
    if (!host) return;
    while(host.firstChild) host.removeChild(host.firstChild);
    url = url.trim();
    if (!url) return;
  function escapeHtml(s) {
    return (s || "").replace(/[&<>"']/g, function (c) {
      return ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" })[c];
    function officeIframe(url) {);
  function officeIframe(url) {
    var src = "https://view.officeapps.live.com/op/embed.aspx?src=" + encodeURIComponent(url);
    return el("iframe", {
      src: src,
      loading: "lazy",
  function ext(url) {
    return "";
  function renderPreview(card) {
    var url = card.getAttribute("data-file-url") || card.getAttribute("data-url") || "";

    if (!url) return "";
    url = url.trim();
    if (/^https?:\/\//i.test(url)) return url;
  }
    if (!url) return "";
    url = url.trim();
    if (/^https?:\/\//i.test(url)) return url;
    var pvB   = el("button", { type:"button", class: "gdy-attach-btn gdy-attach-preview-btn", "data-action":"preview" }, [document.createTextNode("معاينة")]);
    var base = getBaseUrl();
    if (!base) return url; 
    if (url.startsWith("/")) return ${base}${url};
    return ${base}/${url};
  }

  function ext(url) {
    try {
      const u = url.split("?")[0].split("#")[0];
      const m = u.match(/\.([a-z0-9]+)$/i);
      return m ? m[1].toLowerCase() : "";
    } catch (e) { return ""; }
  }

  function isPdf(url) { return ext(url) === "pdf"; }
  function isOffice(url) {
    var e = ext(url);
    return ["doc","docx","xls","xlsx","ppt","pptx"].indexOf(e) !== -1;
  }

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === "class") node.className = attrs[k];
        else if (k === "text") node.textContent = String(attrs[k]);
        else node.setAttribute(k, attrs[k]);
      if (btn) btn.addEventListener("click", function(){ renderPreview(card); });
      var auto = card.getAttribute("data-auto-embed") === "1";);
    }
    children?.forEach(function (c) { node.appendChild(c); });
    return node;
  }

    try {
      var clean = url.split("?")[0].split("#")[0];
      var parts = clean.split("/");
  }
    try {
      const clean = url.split("?")[0].split("#")[0];
      const parts = clean.split("/");
      return decodeURIComponent(parts[parts.length-1] || "ملف");
    } catch (e) {
      return "ملف";
    }
  }

    var openA = el("a", { href: abs, target: "_blank", rel: "noopener", class: "gdy-attach-btn" , "data-action":"open"}, [document.createTextNode("فتح")]);
  }

    var openA = el("a", { href: abs, target: "_blank", rel: "noopener", class: "gdy-attach-btn" , "data-action":"open"}, [document.createTextNode("فتح")]);
    var header = el("div", { class:"gdy-attach-header" }, [
      el("div", { class:"gdy-attach-title", text: "📎 " + String(name) }),
      el("div", { class:"gdy-attach-actions" }, [openA, dlA, pvB])
    pvB.addEventListener("click", function () { renderPreview(card); });
    const dlA   = el("a", { href: abs, class: "gdy-attach-btn", download: "", "data-action":"download"}, [document.createTextNode("تحميل")]);
    const pvB   = el("button", { type:"button", class: "gdy-attach-btn gdy-attach-preview-btn", "data-action":"preview" }, [document.createTextNode("معاينة")]);

    const openA = el("a", { href: abs, target: "_blank", rel: "noopener", class: "gdy-attach-btn" , "data-action":"open"}, [document.createTextNode("فتح")]);
    const dlA   = el("a", { href: abs, class: "gdy-attach-btn", download: "", "data-action":"download"}, [document.createTextNode("تحميل")]);
    const pvB   = el("button", { type:"button", class: "gdy-attach-btn gdy-attach-preview-btn", "data-action":"preview" }, [document.createTextNode("معاينة")]);
      el("div", { class:"gdy-attach-actions" }, [openA, dlA, pvB])
    ]);

  }
      el("div", { class:"gdy-attach-actions" }, [openA, dlA, pvB])
    ]);

    pvB.addEventListener("click", () => renderPreview(card));

    if (autoEmbed) {
      setTimeout(() => renderPreview(card), 30);
    }

  function escapeHtml(s) {
    return (s || "").replace(/[&<>"']/g, function (c) {
      return ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" })[c];
    });
  }

(function () {
  "use strict";
  function convertExistingCards(scope) {
    var cards = scope.querySelectorAll(".gdy-attach-card, .gdy-attachment, .gdy-file-card");
    cards.forEach(function (card) {
      var url = card.getAttribute("data-file-url") || card.getAttribute("data-url") || card.getAttribute("data-src") || "";
      if (!url) {
  function convertExistingCards(scope) {
    var cards = scope.querySelectorAll(".gdy-attach-card, .gdy-attachment, .gdy-file-card");
    cards.forEach((card) => {
      var url = card.getAttribute("data-file-url") || card.getAttribute("data-url") || card.getAttribute("data-src") || "";
      if (!url) {
  }

  function officeIframe(url) {
    var src = "https://view.officeapps.live.com/op/embed.aspx?src=" + encodeURIComponent(url);
    return el("iframe", {
      src: src,
      loading: "lazy",
      referrerpolicy: "no-referrer-when-downgrade",
      class: "gdy-embed-frame",
      allowfullscreen: "true",
      title: "Office Preview"
    }, []);
  }

  function pdfIframe(url) {
    return el("iframe", {
      src: url,
      loading: "lazy",
      class: "gdy-embed-frame",
      title: "PDF Preview"
    }, []);
  }

  function renderPreview(card) {
    var url = card.getAttribute("data-file-url") || card.getAttribute("data-url") || "";
    url = url.trim();
    if (!url) return;

    var host = card.querySelector(".gdy-attach-preview");
    if (!host) return;
    while(host.firstChild) host.removeChild(host.firstChild);

    if (isOffice(url)) {
      host.appendChild(officeIframe(url));
      host.appendChild(el("div", { class:"gdy-embed-note", text: "ملاحظة: معاينة Word/Excel تتطلب رابط ملف عام يمكن الوصول له من الإنترنت." }));
      return;
    }

    if (isPdf(url)) {
      host.appendChild(pdfIframe(url));
      return;
    }

    host.appendChild(el("div", { class:"gdy-embed-note", text: "لا توجد معاينة لهذا النوع من الملفات. استخدم فتح/تحميل." }));
  }

  function convertExistingCards(scope) {
    const cards = scope.querySelectorAll(".gdy-attach-card, .gdy-attachment, .gdy-file-card");
    cards.forEach(function (card) {
      let url = card.getAttribute("data-file-url") || card.getAttribute("data-url") || card.getAttribute("data-src") || "";
      if (!url) {
        var a = card.querySelector("a[href]");
        if (a) url = a.getAttribute("href") || "";
      var btn = card.querySelector("[data-action='preview'], .gdy-attach-preview-btn");
      if (btn) btn.addEventListener("click", function(){ renderPreview(card); });
      url = (url || "").trim();
      if (!url) return;

      if (!card.querySelector(".gdy-attach-preview")) {
        card.appendChild(el("div", { class:"gdy-attach-preview" }, []));
      }

      var btn = card.querySelector("[data-action='preview'], .gdy-attach-preview-btn");
      if (btn) btn.addEventListener("click", function(){ renderPreview(card); });

      if (btn) btn.addEventListener("click", function(){ renderPreview(card); });
      var auto = card.getAttribute("data-auto-embed") === "1";
      if (auto) setTimeout(function(){ renderPreview(card); }, 30);
    });
  }

  function convertPlainLinks(scope) {
    const links = Array.prototype.slice.call(scope.querySelectorAll("a[href]"));
    links.forEach(function (a) {
      var href = (a.getAttribute("href") || "").trim();
      if (!href) return;

      var href = (a.getAttribute("href") || "").trim();
      if (!href) return;

      const href = (a.getAttribute("href") || "").trim();
      if (!href) return;

      var card = buildCard(abs, true);
        else node.setAttribute(k, attrs[k]);
      });
    }
    children?.forEach(function (c) { node.appendChild(c); });
    return node;
  }
        else node.setAttribute(k, attrs[k]);
      });
    }
    children?.forEach(c => node.appendChild(c));
    return node;
  }
      var t = (a.textContent || "").trim();
      if (t && t.length >= 3 && t.length <= 120) {
        var title = card.querySelector(".gdy-attach-title");
        if (title) title.textContent = `📎 ${String(t)}`;
      }

        if (title) title.textContent = "📎 " + String(t);
      }

      if (p.tagName && p.tagName.toLowerCase() === "p" && p.textContent.trim() === a.textContent.trim()) {
        p.parentNode.replaceChild(card, p);
      } else {
        p.replaceChild(card, a);
      }
    });
  }

  function boot() {
    const scope = document.querySelector(".article-body") || document;
    convertExistingCards(scope);
    convertPlainLinks(scope);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();