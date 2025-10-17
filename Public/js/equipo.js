document.addEventListener("DOMContentLoaded", () => {
    // Manejar clic en botón editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('editarEquipoModal'));
            document.getElementById('edit_id_equipo').value = this.dataset.id;
            document.getElementById('edit_nombre_equipo').value = this.dataset.nombre;
            document.getElementById('edit_tipo_equipo').value = this.dataset.tipo;
            document.getElementById('edit_stock').value = this.dataset.stock;
            modal.show();
        });
    });

    // Confirmación al editar equipo
    const formEditar = document.getElementById('formEditarEquipo');
    if (formEditar) {
        formEditar.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: '¿Guardar cambios?',
                text: '¿Estás seguro de que deseas actualizar la información de este equipo?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, guardar cambios',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('✅ Enviando formulario de edición...');
                    // Crear un input hidden para asegurar que el botón llegue al backend
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'editar_equipo';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            });
        });
    }

    // Confirmación al dar de baja
    document.querySelectorAll(".form-baja").forEach(form => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[name]');
            Swal.fire({
                title: "¿Dar de baja este equipo?",
                text: "El equipo quedará inactivo, pero no se eliminará.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#f0ad4e",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, dar de baja",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("✅ Enviando formulario de baja...");
                    // Asegurar que el nombre del botón llegue al backend
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

    // Confirmación al restaurar
    document.querySelectorAll(".form-restaurar").forEach(form => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[name]');
            Swal.fire({
                title: "¿Restaurar este equipo?",
                text: "El equipo volverá a estar activo.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, restaurar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("♻ Restaurando...");
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

    // Confirmación al eliminar
    document.querySelectorAll(".form-eliminar").forEach(form => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[name]');
            Swal.fire({
                title: "¿Eliminar definitivamente?",
                text: "Esta acción no se puede deshacer.",
                icon: "error",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("🗑 Eliminando...");
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
