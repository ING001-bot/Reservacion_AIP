document.addEventListener('DOMContentLoaded', () => {
  // Obtener el modal una sola vez
  const modalElement = document.getElementById('editarUsuarioModal');
  let modalInstance = null;
  
  if (modalElement) {
    // Crear la instancia del modal una sola vez
    modalInstance = new bootstrap.Modal(modalElement);
    
    // Limpiar backdrop al cerrar el modal
    modalElement.addEventListener('hidden.bs.modal', function () {
      // Remover cualquier backdrop residual
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops.forEach(backdrop => backdrop.remove());
      // Restaurar scroll del body
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    });
  }
  
  // Manejar clic en botón editar usuario
  document.querySelectorAll('.btn-editar-usuario').forEach(btn => {
    btn.addEventListener('click', function() {
      if (!modalInstance) {
        alert('Error: No se pudo inicializar el modal');
        return;
      }
      
      // Llenar los datos del formulario
      document.getElementById('edit_id_usuario').value = this.dataset.id;
      document.getElementById('edit_nombre').value = this.dataset.nombre;
      document.getElementById('edit_correo').value = this.dataset.correo;
      // Normalizar teléfono: si vienen 9 dígitos, anteponer +51
      let tel = (this.dataset.telefono || '').replace(/[^0-9+]/g,'');
      if (/^\d{9}$/.test(tel)) { tel = '+51' + tel; }
      if (/^51\d{9}$/.test(tel)) { tel = '+' + tel; }
      document.getElementById('edit_telefono').value = tel;
      document.getElementById('edit_tipo').value = this.dataset.tipo;
      
      // Mostrar el modal
      modalInstance.show();
    });
  });

  // Confirmación al editar usuario
  const formEditarUsuario = document.getElementById('formEditarUsuario');
  if (formEditarUsuario) {
    formEditarUsuario.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      Swal.fire({
        title: '¿Guardar cambios?',
        text: '¿Estás seguro de que deseas actualizar la información de este usuario?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar cambios',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          console.log('✅ Enviando formulario de edición de usuario...');
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'editar_usuario';
          hiddenInput.value = '1';
          form.appendChild(hiddenInput);
          form.submit();
        }
      });
    });
  }

  // Confirmación al eliminar usuario
  document.querySelectorAll('.form-eliminar-usuario').forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = form.querySelector('button[name]');
      Swal.fire({
        title: '¿Eliminar definitivamente?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          if (btn && btn.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = btn.name;
            hidden.value = '1';
            form.appendChild(hidden);
          }
          form.submit();
        }
      });
    });
  });
});
