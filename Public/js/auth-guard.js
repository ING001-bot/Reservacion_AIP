// Public/js/auth-guard.js
// Script de protección para páginas autenticadas
// Previene acceso mediante botón atrás después de logout

(function() {
  'use strict';

  // Función para validar sesión en servidor
  function checkSession() {
    return fetch('/Reservacion_AIP/app/api/check_session.php', { 
      method: 'GET',
      cache: 'no-store',
      credentials: 'same-origin'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
      return data.logged_in === true;
    })
    .catch(function() {
      return false; // En caso de error, asumir no autenticado
    });
  }

  // Detectar si la página se carga desde caché (bfcache) después de logout
  window.addEventListener('pageshow', function(event) {
    // event.persisted = true significa que viene del cache del navegador (bfcache)
    if (event.persisted) {
      // La página se cargó desde caché (usuario hizo click en flecha atrás)
      console.log('Página cargada desde caché, validando sesión...');
      
      checkSession().then(function(isLoggedIn) {
        if (!isLoggedIn) {
          // No hay sesión activa, redirigir al login
          console.log('Sesión no válida, redirigiendo al login...');
          window.location.replace('../../Public/index.php');
        } else {
          console.log('Sesión válida, permitiendo acceso');
        }
      });
    }
  });

  // Limpiar history al hacer logout para prevenir volver con botón atrás
  var logoutLinks = document.querySelectorAll('a[href*="LogoutController"], button[onclick*="logout"]');
  logoutLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      // Marcar que se hizo logout en sessionStorage
      sessionStorage.setItem('logged_out', 'true');
      
      // Limpiar el estado de navegación
      if (window.history && window.history.pushState) {
        // Reemplazar estado actual para que no se pueda volver
        window.history.replaceState(null, '', window.location.href);
      }
    });
  });

  // Al cargar la página, verificar si se hizo logout recientemente
  if (sessionStorage.getItem('logged_out') === 'true') {
    // Limpiar el flag
    sessionStorage.removeItem('logged_out');
    console.log('Logout detectado, validando sesión...');
    
    // Verificar sesión antes de redirigir
    checkSession().then(function(isLoggedIn) {
      if (!isLoggedIn) {
        // Redirigir al login si no hay sesión
        console.log('Sin sesión después de logout, redirigiendo...');
        window.location.replace('../../Public/index.php');
      }
    });
  }

})();
