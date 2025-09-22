// Public/js/cambiar_contraseña.js
// Funciones para la vista Cambiar_Contraseña
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  if (!field) return;
  const icon = field.parentElement ? field.parentElement.querySelector('.toggle-password i') : null;
  if (field.type === 'password') {
    field.type = 'text';
    if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
  } else {
    field.type = 'password';
    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
  }
}

function updateRequirement(element, isValid) {
  if (!element) return;
  const icon = element.querySelector('i');
  if (!icon) return;
  if (isValid) {
    element.classList.add('valid');
    element.classList.remove('invalid');
    icon.className = 'fas fa-check-circle';
  } else {
    element.classList.add('invalid');
    element.classList.remove('valid');
    icon.className = 'far fa-times-circle';
  }
}

function checkPasswordStrength(password) {
  const strengthBar = document.getElementById('strengthBar');
  const length = document.getElementById('length');
  const uppercase = document.getElementById('uppercase');
  const number = document.getElementById('number');
  const special = document.getElementById('special');
  const submitBtn = document.getElementById('submitBtn');

  // Reset
  [length, uppercase, number, special].forEach(el => {
    if (el) {
      el.classList.remove('valid', 'invalid');
      const icon = el.querySelector('i');
      if (icon) icon.className = 'far fa-circle';
    }
  });

  let strength = 0;
  if (password.length >= 8) { strength++; updateRequirement(length, true); } else { updateRequirement(length, false); }
  if (/[A-Z]/.test(password)) { strength++; updateRequirement(uppercase, true); } else { updateRequirement(uppercase, false); }
  if (/[0-9]/.test(password)) { strength++; updateRequirement(number, true); } else { updateRequirement(number, false); }
  if (/[^A-Za-z0-9]/.test(password)) { strength++; updateRequirement(special, true); } else { updateRequirement(special, false); }

  if (strengthBar) {
    const strengthPercent = (strength / 4) * 100;
    strengthBar.style.width = strengthPercent + '%';
    if (strength <= 1) { strengthBar.style.backgroundColor = '#e74c3c'; }
    else if (strength === 2) { strengthBar.style.backgroundColor = '#f39c12'; }
    else if (strength === 3) { strengthBar.style.backgroundColor = '#3498db'; }
    else { strengthBar.style.backgroundColor = '#2ecc71'; }
  }

  const allRequirementsMet = document.querySelectorAll('.requirement.valid').length === 4;
  if (submitBtn) submitBtn.disabled = !allRequirementsMet;

  const confirmField = document.getElementById('confirmar');
  const matchText = document.getElementById('passwordMatch');
  if (confirmField && matchText) {
    const confirmPassword = confirmField.value;
    if (confirmPassword) {
      if (password === confirmPassword) {
        matchText.textContent = 'Las contraseñas coinciden';
        matchText.style.color = '#2ecc71';
        if (submitBtn) submitBtn.disabled = !allRequirementsMet;
      } else {
        matchText.textContent = 'Las contraseñas no coinciden';
        matchText.style.color = '#e74c3c';
        if (submitBtn) submitBtn.disabled = true;
      }
    } else {
      matchText.textContent = '';
      if (submitBtn) submitBtn.disabled = true;
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const confirmField = document.getElementById('confirmar');
  if (confirmField) {
    confirmField.addEventListener('input', function() {
      const password = document.getElementById('nueva').value;
      const confirmPassword = this.value;
      const matchText = document.getElementById('passwordMatch');
      const submitBtn = document.getElementById('submitBtn');
      if (password && confirmPassword) {
        if (password === confirmPassword) {
          matchText.textContent = 'Las contraseñas coinciden';
          matchText.style.color = '#2ecc71';
          if (submitBtn) submitBtn.disabled = false;
        } else {
          matchText.textContent = 'Las contraseñas no coinciden';
          matchText.style.color = '#e74c3c';
          if (submitBtn) submitBtn.disabled = true;
        }
      } else {
        matchText.textContent = '';
        if (submitBtn) submitBtn.disabled = true;
      }
    });
  }
});
