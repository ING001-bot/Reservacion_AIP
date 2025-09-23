// Public/js/registrar_usuario.js
// Mostrar/Ocultar contraseña en los formularios de Registrar_Usuario (admin y público)
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
