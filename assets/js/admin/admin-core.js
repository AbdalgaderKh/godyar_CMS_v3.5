(function(){const SIDEBAR_KEY='godyar_sidebar_visible';const DARK_KEY='godyar_dark';
const qs=(s,r)=> (r||document).querySelector(s);const qsa=(s,r)=>Array.from((r||document).querySelectorAll(s));
function openSidebar(){document.body.classList.add('admin-sidebar-open');const b=qs('#gdyAdminBackdrop');if(b)b.hidden=false;}
function closeSidebar(){document.body.classList.remove('admin-sidebar-open');const b=qs('#gdyAdminBackdrop');if(b)b.hidden=true;}
function initMobileSidebar(){const openBtn=qs('#gdyAdminMenuBtn');const closeBtn=qs('#sidebarToggle');const backdrop=qs('#gdyAdminBackdrop');
if(openBtn)openBtn.addEventListener('click',openSidebar);if(closeBtn)closeBtn.addEventListener('click',closeSidebar);if(backdrop)backdrop.addEventListener('click',closeSidebar);
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSidebar();});
qsa('.admin-sidebar a').forEach(a=>a.addEventListener('click',()=>{if(matchMedia('(max-width: 991.98px)').matches)closeSidebar();}));}
function initSidebarSearch(){const input=qs('#sidebarSearch');const box=qs('#sidebarSearchResults');const cards=qsa('.admin-sidebar__link-card');
if(!input||!box)return;const hide=()=>box.style.display='none';const show=()=>box.style.display='block';
input.addEventListener('input',()=>{const q=(input.value||'').trim().toLowerCase();box.innerHTML='';if(!q){hide();return;}
const matches=cards.filter(c=>(((c.getAttribute('data-search')||'')+' '+(c.textContent||'')).toLowerCase().includes(q))).slice(0,12);
show();if(!matches.length){const d=document.createElement('div');d.className='admin-sidebar__search-result-item';d.textContent='لا توجد نتائج مطابقة';box.appendChild(d);return;}
matches.forEach(card=>{const link=card.querySelector('a');if(!link)return;const lbl=(card.querySelector('.admin-sidebar__link-label')||{}).textContent||link.textContent;
const a=document.createElement('a');a.href=link.getAttribute('href');a.className='admin-sidebar__search-result-item';a.textContent=(lbl||'').trim();box.appendChild(a);});});
document.addEventListener('click',e=>{if(!box.contains(e.target)&&e.target!==input)hide();});}
function initDarkMode(){const btn=qs('#darkModeToggle');if(!btn)return;if(localStorage.getItem(DARK_KEY)==='1')document.body.classList.add('godyar-dark');
btn.addEventListener('click',()=>{document.body.classList.toggle('godyar-dark');localStorage.setItem(DARK_KEY,document.body.classList.contains('godyar-dark')?'1':'0');});}
function initGlobalSidebarVisibility(){const btn=qs('#gdy-sidebar-global-toggle');if(!btn)return;const label=btn.querySelector('.gdy-sidebar-global-label');
function refresh(){let v=localStorage.getItem(SIDEBAR_KEY);if(v===null)v='1';if(label)label.textContent=(v==='1')?'إخفاء القائمة الجانبية في الواجهة':'إظهار القائمة الجانبية في الواجهة';
btn.title=(v==='1')?'الحالة الحالية: القائمة الجانبية ظاهرة':'الحالة الحالية: القائمة الجانبية مخفية';}
refresh();btn.addEventListener('click',()=>{let v=localStorage.getItem(SIDEBAR_KEY);if(v===null)v='1';localStorage.setItem(SIDEBAR_KEY,(v==='1')?'0':'1');refresh();});}
function initLiveDateTime(){const d=qs('.gdy-date-value');const t=qs('.gdy-time-value');if(!d&&!t)return;
const tick=()=>{const now=new Date();if(d)d.textContent=now.toLocaleDateString(document.documentElement.lang||'ar-EG');if(t)t.textContent=now.toLocaleTimeString(document.documentElement.lang||'ar-EG');};
tick();setInterval(tick,1000);}
document.addEventListener('DOMContentLoaded',()=>{initMobileSidebar();initSidebarSearch();initDarkMode();initGlobalSidebarVisibility();initLiveDateTime();});
})();
