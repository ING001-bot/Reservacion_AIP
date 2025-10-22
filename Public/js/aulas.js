document.addEventListener('DOMContentLoaded', () => {
  // Manejar clic en botón editar aula
  document.querySelectorAll('.btn-editar-aula').forEach(btn => {
    btn.addEventListener('click', function() {
      const modal = new bootstrap.Modal(document.getElementById('editarAulaModal'));
      document.getElementById('edit_id_aula').value = this.dataset.id;
      document.getElementById('edit_nombre_aula').value = this.dataset.nombre;
      document.getElementById('edit_capacidad').value = this.dataset.capacidad;
      document.getElementById('edit_tipo').value = this.dataset.tipo;
      modal.show();
    });
  });

  // Confirmación al editar aula
  const formEditarAula = document.getElementById('formEditarAula');
  if (formEditarAula) {
    formEditarAula.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      Swal.fire({
        title: '¿Guardar cambios?',
        text: '¿Estás seguro de que deseas actualizar la información de esta aula?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar cambios',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          console.log('✅ Enviando formulario de edición de aula...');
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'editar_aula';
          hiddenInput.value = '1';
          form.appendChild(hiddenInput);
          form.submit();
        }
      });
    });
  }

  // Confirmación al eliminar aula
  document.querySelectorAll('.form-eliminar-aula').forEach((form) => {
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
