document.addEventListener("DOMContentLoaded", () => {
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
