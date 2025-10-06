// Crear Administrador - UX 2025
(function(){
  const pass = document.getElementById('admin-pass');
  const toggle = document.getElementById('toggle-pass');
  const bar = document.getElementById('strength-bar');
  const wrap = document.getElementById('strength');

  function zxcv(str){
    if(!str) return 0;
    let score = 0;
    if (str.length >= 6) score += 1;
    if (str.length >= 10) score += 1;
    if (/[A-Z]/.test(str)) score += 1;
    if (/[a-z]/.test(str)) score += 1;
    if (/\d/.test(str)) score += 1;
    if (/[^A-Za-z0-9]/.test(str)) score += 1;
    return Math.min(score, 5);
  }

  function renderStrength(v){
    const pct = [0,20,40,60,80,100][v];
    bar.style.width = pct + '%';
    wrap.classList.remove('ok','mid');
    if (v >= 4) wrap.classList.add('ok');
    else if (v >= 2) wrap.classList.add('mid');
  }

  if (pass){
    pass.addEventListener('input', function(){
      renderStrength(zxcv(pass.value));
    });
  }

  if (toggle){
    toggle.addEventListener('click', function(e){
      e.preventDefault();
      if (!pass) return;
      pass.type = pass.type === 'password' ? 'text' : 'password';
      toggle.querySelector('i')?.classList.toggle('fa-eye-slash');
    });
  }
})();
