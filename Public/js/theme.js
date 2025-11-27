// Public/js/theme.js
// Theme toggle - DESHABILITADO (botón eliminado del navbar)
(function(){
  // Funcionalidad de tema deshabilitada
  // El botón fue eliminado porque no funcionaba correctamente
  
  /*
  var retryCount = 0;
  var maxRetries = 10;
  
  function applySavedTheme(){
    try {
      var saved = localStorage.getItem('theme');
      console.log('🎨 Theme.js: Tema guardado:', saved);
      if (saved === 'dark') { 
        document.body.classList.add('dark');
        console.log('🌙 Theme.js: Modo oscuro aplicado desde localStorage');
      }
      // Intentar actualizar el icono si el botón ya existe
      setTimeout(function() { updateThemeIcon(saved === 'dark'); }, 100);
    } catch(e) {
      console.error('❌ Theme.js: Error al aplicar tema:', e);
    }
  }
  
  function updateThemeIcon(isDark){
    var btn = document.getElementById('theme-toggle-navbar');
    if (!btn) {
      console.warn('⚠️ Theme.js: Botón no encontrado aún para actualizar icono');
      return;
    }
    var icon = btn.querySelector('i');
    if (!icon) {
      console.warn('⚠️ Theme.js: Icono no encontrado en botón');
      return;
    }
    if (isDark) {
      icon.className = 'fas fa-sun fa-lg';
      btn.title = 'Cambiar a modo claro';
      console.log('☀️ Theme.js: Icono cambiado a sol (modo oscuro activo)');
    } else {
      icon.className = 'fas fa-moon fa-lg';
      btn.title = 'Cambiar a modo oscuro';
      console.log('🌙 Theme.js: Icono cambiado a luna (modo claro activo)');
    }
  }
  
  function bindToggle(){
    var btn = document.getElementById('theme-toggle-navbar');
    if (!btn) {
      retryCount++;
      if (retryCount < maxRetries) {
        console.log('⏳ Theme.js: Reintentando encontrar botón (' + retryCount + '/' + maxRetries + ')...');
        setTimeout(bindToggle, 300);
      } else {
        console.error('❌ Theme.js: Botón de tema NO encontrado después de', maxRetries, 'intentos');
      }
      return;
    }
    
    console.log('✅ Theme.js: Botón encontrado, vinculando evento click');
    
    btn.addEventListener('click', function(){
      console.log('🖱️ Theme.js: Click en botón de tema detectado');
      document.body.classList.toggle('dark');
      var isDark = document.body.classList.contains('dark');
      console.log('🎨 Theme.js: Tema cambiado a:', isDark ? 'oscuro' : 'claro');
      try { 
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        console.log('💾 Theme.js: Tema guardado en localStorage');
      } catch(e) {
        console.error('❌ Theme.js: Error al guardar en localStorage:', e);
      }
      updateThemeIcon(isDark);
    });
    
    // Actualizar icono inicial
    var isDark = document.body.classList.contains('dark');
    updateThemeIcon(isDark);
    console.log('🎉 Theme.js: Sistema de tema completamente inicializado');
  }
  */
  
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
    // applySavedTheme(); // DESHABILITADO
    // bindToggle(); // DESHABILITADO
    ensureTableScrollWrappers();
    observeDynamicTables();
    bindConfirmations();
  });
})();
