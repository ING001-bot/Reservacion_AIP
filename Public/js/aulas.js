document.addEventListener('DOMContentLoaded', () => {
  // Confirmación al eliminar aula (mismo estilo que equipos)
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
          // Asegurar que el backend detecte la acción por name del botón
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
