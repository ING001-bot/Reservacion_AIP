document.addEventListener('DOMContentLoaded', () => {
  // Confirmación al eliminar aula (mismo estilo que equipos)
  document.querySelectorAll('.btn-eliminar-aula').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const id = btn.dataset.id;
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
          window.location.href = `?eliminar=${encodeURIComponent(id)}`;
        }
      });
    });
  });
});
