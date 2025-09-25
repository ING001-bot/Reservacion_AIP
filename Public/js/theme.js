// Public/js/theme.js
// Injects a floating dark-mode toggle and persists user choice across pages
(function(){
  function applySavedTheme(){
    try {
      var saved = localStorage.getItem('theme');
      if (saved === 'dark') { document.body.classList.add('dark'); }
    } catch(e) {}
  }
  function buildToggle(){
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'theme-fab';
    btn.title = 'Cambiar tema';
    btn.setAttribute('aria-label', 'Cambiar tema');
    btn.textContent = '🌓';
    btn.addEventListener('click', function(){
      document.body.classList.toggle('dark');
      var isDark = document.body.classList.contains('dark');
      try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch(e) {}
    });
    document.body.appendChild(btn);
  }
  document.addEventListener('DOMContentLoaded', function(){
    applySavedTheme();
    buildToggle();
  });
})();
