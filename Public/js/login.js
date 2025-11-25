// Public/js/login.js
// Prevenir acceso a login si ya hay sesión activa (validación extra)
(function() {
  // Detectar si venimos de un logout usando performance navigation
  if (window.performance) {
    var navType = window.performance.navigation.type;
    // Si es navegación hacia atrás (type 2), recargar forzadamente desde servidor
    if (navType === 2) {
      window.location.reload(true);
    }
  }
})();

// Toggle mostrar/ocultar contraseña en login (mismo comportamiento que Cambiar_Contraseña)
function togglePassword(fieldId) {
  var field = document.getElementById(fieldId);
  if (!field) return;
  var icon = field.parentElement ? field.parentElement.querySelector('.toggle-password i') : null;
  if (field.type === 'password') {
    field.type = 'text';
    if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
  } else {
    field.type = 'password';
    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
  }
}

// Mejores interacciones del login
document.addEventListener('DOMContentLoaded', function () {
  // Theme toggle (persistir en localStorage)
  var themeBtn = document.getElementById('theme-toggle');
  var savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    document.body.classList.add('dark');
  }
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      document.body.classList.toggle('dark');
      var isDark = document.body.classList.contains('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
  }

  // Loading state al enviar
  var form = document.querySelector('form.login-form');
  var submitBtn = document.getElementById('login-submit');
  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.classList.add('loading');
      submitBtn.disabled = true;
      // feedback visual
      var prev = submitBtn.textContent;
      submitBtn.dataset.prevText = prev;
      submitBtn.textContent = 'Ingresando…';
    });
  }
});
