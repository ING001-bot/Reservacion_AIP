// Public/js/theme.js
// Theme toggle - ahora integrado en navbar
(function(){
  function applySavedTheme(){
    try {
      var saved = localStorage.getItem('theme');
      if (saved === 'dark') { 
        document.body.classList.add('dark');
      }
      // Intentar actualizar el icono si el botón ya existe
      setTimeout(function() { updateThemeIcon(saved === 'dark'); }, 100);
    } catch(e) {}
  }
  
  function updateThemeIcon(isDark){
    var btn = document.getElementById('theme-toggle-navbar');
    if (!btn) return;
    var icon = btn.querySelector('i');
    if (!icon) return;
    if (isDark) {
      icon.className = 'fas fa-sun fa-lg';
      btn.title = 'Cambiar a modo claro';
    } else {
      icon.className = 'fas fa-moon fa-lg';
      btn.title = 'Cambiar a modo oscuro';
    }
  }
  
  function bindToggle(){
    var btn = document.getElementById('theme-toggle-navbar');
    if (!btn) {
      // Reintentar en 500ms si el navbar aún no se ha cargado
      setTimeout(bindToggle, 500);
      return;
    }
    
    btn.addEventListener('click', function(){
      document.body.classList.toggle('dark');
      var isDark = document.body.classList.contains('dark');
      try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch(e) {}
      updateThemeIcon(isDark);
    });
    
    // Actualizar icono inicial
    var isDark = document.body.classList.contains('dark');
    updateThemeIcon(isDark);
  }
  function ensureTableScrollWrappers(){
    try{
      var tables = Array.prototype.slice.call(document.querySelectorAll('table'));
      tables.forEach(function(tbl){
        // si ya está dentro de un contenedor con overflow horizontal, saltar
        var parent = tbl.parentElement;
        var hasResponsive = parent && (parent.classList.contains('table-responsive') || parent.classList.contains('mobile-scroll'));
        if (hasResponsive) return;
        // crear contenedor
        var wrap = document.createElement('div');
        wrap.className = 'table-responsive';
        wrap.style.overflowX = 'auto';
        wrap.style.webkitOverflowScrolling = 'touch';
        // insertar y mover tabla dentro
        parent.insertBefore(wrap, tbl);
        wrap.appendChild(tbl);
      });
    }catch(e){}
  }
  function observeDynamicTables(){
    try{
      var mo = new MutationObserver(function(muts){
        var needs = false;
        muts.forEach(function(m){
          if (m.addedNodes && m.addedNodes.length){ needs = true; }
        });
        if (needs){ ensureTableScrollWrappers(); }
      });
      mo.observe(document.body, { childList: true, subtree: true });
    }catch(e){}
  }
  function bindConfirmations(){
    if (!window.Swal) return; // require SweetAlert2
    document.addEventListener('click', function(e){
      var el = e.target.closest('[data-confirm]');
      if (!el) return;
      // evitar doble
      if (el.__confirming) return;
      e.preventDefault();
      var msg = el.getAttribute('data-confirm') || '¿Confirmar acción?';
      var confirmText = el.getAttribute('data-confirm-ok') || 'Sí, continuar';
      var cancelText = el.getAttribute('data-confirm-cancel') || 'Cancelar';
      el.__confirming = true;
      Swal.fire({
        title: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#1e6bd6'
      }).then(function(res){
        el.__confirming = false;
        if (!res.isConfirmed) return;
        // Si es botón dentro de form, enviar form
        var form = el.closest('form');
        if (form){ form.submit(); return; }
        // Si es enlace
        var href = el.getAttribute('href');
        if (href){ window.location.href = href; return; }
        // Fallback: trigger click nativo sin data-confirm
        el.removeAttribute('data-confirm');
        el.click();
        // restaurar atributo por si se reutiliza
        setTimeout(function(){ el.setAttribute('data-confirm', msg); }, 0);
      });
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    applySavedTheme();
    bindToggle();
    ensureTableScrollWrappers();
    observeDynamicTables();
    bindConfirmations();
  });
})();
