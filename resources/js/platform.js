// Small UI niceties for Orchid
// 1) Ripple effect on buttons
(function(){
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn');
    if(!btn) return;
    const ripple = document.createElement('span');
    ripple.className='ripple';
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.width=ripple.style.height=size+'px';
    ripple.style.left=(e.clientX-rect.left-size/2)+'px';
    ripple.style.top=(e.clientY-rect.top-size/2)+'px';
    btn.style.position='relative';
    btn.style.overflow='hidden';
    btn.appendChild(ripple);
    setTimeout(()=>ripple.remove(), 500);
  });
})();

// 2) Auto highlight active sidebar group on hover
(function(){
  const aside = document.querySelector('.layout .aside');
  if(!aside) return;
  aside.addEventListener('mouseover',e=>{
    const link = e.target.closest('.menu-link');
    if(link) link.classList.add('hovering');
  });
  aside.addEventListener('mouseout',e=>{
    const link = e.target.closest('.menu-link');
    if(link) link.classList.remove('hovering');
  });
})();

// Styles for ripple
const style = document.createElement('style');
style.textContent = `.ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,.35);transform:scale(0);animation:ripple .45s ease-out;pointer-events:none}
@keyframes ripple{to{transform:scale(2.5);opacity:0}}`;
document.head.appendChild(style);

// 3) Marcar body cuando es la vista de login (Orchid: .form-signin) con soporte Turbo
(function(){
  const applyLoginBg = ()=>{
    const isLogin = !!document.querySelector('.form-signin');
    document.body.classList.toggle('login-bg', isLogin);
  };
  // Al cargar DOM, al render Turbo y en cambios de página
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', applyLoginBg, {once:true});
  } else {
    applyLoginBg();
  }
  document.addEventListener('turbo:load', applyLoginBg);
  document.addEventListener('turbo:render', applyLoginBg);
})();

// 4) Parallax suave del fondo del login (solo cuando login-bg está activo)
(function(){
  const updateParallax = (x=0,y=0)=>{
    if(!document.body.classList.contains('login-bg')) return;
    document.body.style.setProperty('--parallax-x', x+'px');
    document.body.style.setProperty('--parallax-y', y+'px');
  };
  let rafId;
  const onMove = (e)=>{
    if(!document.body.classList.contains('login-bg')) return;
    const w = window.innerWidth, h = window.innerHeight;
    const cx = (e.clientX ?? w/2) / w - 0.5;
    const cy = (e.clientY ?? h/2) / h - 0.5;
    const x = Math.max(-8, Math.min(8, cx * -16));
    const y = Math.max(-8, Math.min(8, cy * -16));
    cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(()=>updateParallax(x,y));
  };
  window.addEventListener('mousemove', onMove);
  window.addEventListener('scroll', ()=>{
    if(!document.body.classList.contains('login-bg')) return;
    const y = Math.max(-10, Math.min(10, window.scrollY * -0.02));
    updateParallax(0,y);
  });
})();

// 5) Data-theme por módulo según la ruta
(function(){
  const applyTheme = ()=>{
    const href = location.pathname.toLowerCase();
    let theme = '';
    if(href.includes('/admin/users')) theme = 'usuarios';
    else if(href.includes('/admin/roles')) theme = 'roles';
    else if(href.includes('/admin/producto') || href.includes('/admin/productos') || href.includes('/admin/crud/list/producto-resources')) theme = 'productos';
    else if(href.includes('/admin/inventario') || href.includes('/admin/inventarios')) theme = 'inventarios';
    else if(href.includes('/admin/proyecto') || href.includes('/admin/proyectos')) theme = 'proyectos';
    else if(href.includes('/admin/escena') || href.includes('/admin/escenas')) theme = 'escenas';
    document.body.dataset.theme = theme;

    // Vista Tabla/Tarjetas para listado de productos (ruta del CRUD Resource)
    const isProductsList = href.includes('/admin/crud/list/producto-resources');
    if (isProductsList) {
      const params = new URLSearchParams(location.search);
      const urlView = params.get('view'); // 'cards' | 'table' | null
      let pref = localStorage.getItem('productsView');
      if (urlView === 'cards' || urlView === 'table') {
        pref = urlView;
        localStorage.setItem('productsView', pref);
      }
      if (pref !== 'cards' && pref !== 'table') pref = 'cards';
      document.body.classList.toggle('cards-grid-products', pref === 'cards');
      ensureProductsViewToggle(pref);
    } else {
      removeProductsViewToggle();
      document.body.classList.remove('cards-grid-products');
    }
  };
  applyTheme();
  window.addEventListener('turbo:load', applyTheme);
  window.addEventListener('turbo:render', applyTheme);
})();

// 6) Chatbot flotante global (usa /api/chatbot)
(function(){
  const createChatUi = ()=>{
    if(document.getElementById('aiChatToggle')) return;

    const btn = document.createElement('button');
    btn.id = 'aiChatToggle';
    btn.type = 'button';
    btn.className = 'btn btn-primary btn-sm';
    btn.textContent = 'Asistente';
    btn.style.position = 'fixed';
    btn.style.right = '18px';
    btn.style.bottom = '70px';
    btn.style.zIndex = '1100';
    btn.style.borderRadius = '9999px';
    btn.style.boxShadow = '0 10px 25px rgba(15,23,42,.35)';

    const panel = document.createElement('div');
    panel.id = 'aiChatPanel';
    panel.style.position = 'fixed';
    panel.style.right = '18px';
    panel.style.bottom = '120px';
    panel.style.width = '320px';
    panel.style.maxHeight = '420px';
    panel.style.display = 'none';
    panel.style.flexDirection = 'column';
    panel.style.background = '#ffffff';
    panel.style.borderRadius = '14px';
    panel.style.boxShadow = '0 18px 45px rgba(15,23,42,.35)';
    panel.style.overflow = 'hidden';
    panel.style.zIndex = '1100';

    panel.innerHTML = `
      <div style="padding:8px 12px;border-bottom:1px solid rgba(15,23,42,.08);display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;">
        <div>
          <div style="font-weight:600;font-size:14px;">Asistente del sistema</div>
          <div style="font-size:11px;opacity:.9;">Haz preguntas sobre el módulo actual</div>
        </div>
        <button type="button" id="aiChatClose" class="btn btn-sm btn-light" style="font-size:11px;padding:2px 6px;border-radius:999px;">×</button>
      </div>
      <div id="aiChatMessages" style="flex:1;padding:10px 10px 6px;overflow-y:auto;font-size:13px;background:#f9fafb;"></div>
      <form id="aiChatForm" style="display:flex;gap:6px;padding:8px 10px;border-top:1px solid rgba(15,23,42,.06);background:#ffffff;">
        <input id="aiChatInput" type="text" class="form-control" placeholder="Escribe tu pregunta..." autocomplete="off" style="font-size:13px;">
        <button class="btn btn-primary btn-sm" type="submit" style="white-space:nowrap;">Enviar</button>
      </form>
    `;

    document.body.appendChild(btn);
    document.body.appendChild(panel);

    const messagesEl = panel.querySelector('#aiChatMessages');
    const formEl = panel.querySelector('#aiChatForm');
    const inputEl = panel.querySelector('#aiChatInput');
    const closeEl = panel.querySelector('#aiChatClose');

    const appendMessage = (who, text)=>{
      const row = document.createElement('div');
      row.style.marginBottom = '6px';
      row.innerHTML = `<div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:1px;">${who}</div><div style="padding:6px 8px;border-radius:8px;background:${who==='Tú' ? '#e5e7eb' : '#eef2ff'};color:#111827;white-space:pre-wrap;">${text}</div>`;
      messagesEl.appendChild(row);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    btn.addEventListener('click', ()=>{
      panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
      if(panel.style.display === 'flex'){
        inputEl.focus();
      }
    });
    closeEl.addEventListener('click', ()=>{
      panel.style.display = 'none';
    });

    formEl.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const message = inputEl.value.trim();
      if(!message) return;
      inputEl.value = '';
      appendMessage('Tú', message);

      const module = document.body.dataset.theme || '';
      appendMessage('Asistente', 'Pensando...');

      try {
        const resp = await fetch('/api/chatbot', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({message, module}),
        });
        const data = await resp.json();
        messagesEl.lastChild.remove(); // quitar "Pensando..."

        if (!resp.ok) {
          appendMessage('Asistente', data.reply || `Error HTTP ${resp.status} al llamar al asistente.`);
          return;
        }

        appendMessage('Asistente', data.reply || 'No se pudo obtener respuesta del asistente.');
      } catch (err) {
        messagesEl.lastChild.remove();
        appendMessage('Asistente', 'Ocurrió un error al contactar con el asistente. Detalle técnico: ' + (err && err.message ? err.message : 'sin detalles.'));
      }
    });
  };

  const init = ()=>{
    if(document.body.classList.contains('login-bg')) return; // no en login
    createChatUi();
  };

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init, {once:true});
  } else {
    init();
  }
  document.addEventListener('turbo:load', init);
  document.addEventListener('turbo:render', init);
})();

// UI: botón fijo para alternar Tabla/Tarjetas en listado de productos
function ensureProductsViewToggle(current){
  let btn = document.getElementById('viewToggleProducts');
  if(!btn){
    btn = document.createElement('button');
    btn.id = 'viewToggleProducts';
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-primary';
    btn.style.position = 'fixed';
    btn.style.right = '16px';
    btn.style.bottom = '18px';
    btn.style.zIndex = '1000';
    btn.style.boxShadow = '0 8px 20px rgba(17,24,39,.25)';
    document.body.appendChild(btn);
    btn.addEventListener('click', ()=>{
      const params = new URLSearchParams(location.search);
      let pref = localStorage.getItem('productsView')||'cards';
      pref = pref === 'cards' ? 'table' : 'cards';
      localStorage.setItem('productsView', pref);
      // actualizar clase
      document.body.classList.toggle('cards-grid-products', pref === 'cards');
      // actualizar URL sin recargar
      params.set('view', pref);
      history.replaceState(null, '', location.pathname + '?' + params.toString());
      // actualizar etiqueta
      btn.textContent = pref === 'cards' ? 'Vista: Tarjetas' : 'Vista: Tabla';
    });
  }
  btn.textContent = current === 'cards' ? 'Vista: Tarjetas' : 'Vista: Tabla';
}

function removeProductsViewToggle(){
  const btn = document.getElementById('viewToggleProducts');
  if(btn && btn.parentNode){
    btn.parentNode.removeChild(btn);
  }
}
