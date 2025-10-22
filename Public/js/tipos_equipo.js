document.addEventListener('DOMContentLoaded', function(){
  // Manejar clic en botón editar tipo
  document.querySelectorAll('.btn-editar-tipo').forEach(btn => {
    btn.addEventListener('click', function() {
      const modal = new bootstrap.Modal(document.getElementById('editarTipoModal'));
      document.getElementById('edit_id_tipo').value = this.dataset.id;
      document.getElementById('edit_nombre_tipo').value = this.dataset.nombre;
      modal.show();
    });
  });

  // Confirmación al editar tipo
  const formEditarTipo = document.getElementById('formEditarTipo');
  if (formEditarTipo) {
    formEditarTipo.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      Swal.fire({
        title: '¿Guardar cambios?',
        text: '¿Estás seguro de que deseas actualizar este tipo de equipo?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar cambios',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          console.log('✅ Enviando formulario de edición de tipo...');
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'editar_tipo';
          hiddenInput.value = '1';
          form.appendChild(hiddenInput);
          form.submit();
        }
      });
    });
  }

  // Confirmación para eliminar tipo de equipo
  document.querySelectorAll('.form-eliminar-tipo').forEach(function(form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const btn = form.querySelector('button[name]');
      Swal.fire({
        title: '¿Eliminar este tipo?',
        text: 'No podrá eliminarse si hay equipos asociados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((r)=>{
        if(r.isConfirmed){
          if (btn && btn.name){
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = btn.name; hidden.value = '1';
            form.appendChild(hidden);
          }
          form.submit();
        }
      })
    })
  })
});
