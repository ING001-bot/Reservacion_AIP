document.addEventListener('DOMContentLoaded', function(){
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
