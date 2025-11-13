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
    else if(href.includes('/admin/producto') || href.includes('/admin/productos')) theme = 'productos';
    else if(href.includes('/admin/inventario') || href.includes('/admin/inventarios')) theme = 'inventarios';
    else if(href.includes('/admin/proyecto') || href.includes('/admin/proyectos')) theme = 'proyectos';
    else if(href.includes('/admin/escena') || href.includes('/admin/escenas')) theme = 'escenas';
    document.body.dataset.theme = theme;
  };
  applyTheme();
  window.addEventListener('turbo:load', applyTheme);
  window.addEventListener('turbo:render', applyTheme);
})();
