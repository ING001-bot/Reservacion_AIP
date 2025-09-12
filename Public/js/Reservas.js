document.addEventListener('DOMContentLoaded', () => {
    const fechaInput = document.getElementById('fecha-select');
    const aulaSelect = document.getElementById('aula-select');
    const cuadroHoras = document.getElementById('cuadro-horas');
    const fechaBadge = document.getElementById('fecha-badge');
    const btnReservar = document.getElementById('btn-reservar');
    const formReserva = document.getElementById('form-reserva');

    function actualizarHoras() {
        const fecha = fechaInput.value;
        const aula = aulaSelect.value;
        if (!fecha || !aula) {
            cuadroHoras.innerHTML = "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
            return;
        }
        fechaBadge.textContent = fecha;
        fetch(`actualizar_horas.php?id_aula=${aula}&fecha=${fecha}`)
            .then(res => res.text())
            .then(html => {
                cuadroHoras.innerHTML = html;
            });
    }

    actualizarHoras();

    fechaInput.addEventListener('change', actualizarHoras);
    aulaSelect.addEventListener('change', actualizarHoras);

    // Confirmación al reservar
    btnReservar.addEventListener('click', () => {
        const aula = formReserva.querySelector("[name='id_aula']").value;
        const fecha = formReserva.querySelector("[name='fecha']").value;
        const horaInicio = formReserva.querySelector("[name='hora_inicio']").value;
        const horaFin = formReserva.querySelector("[name='hora_fin']").value;

        if (!aula || !fecha || !horaInicio || !horaFin) {
            Swal.fire("⚠️ Campos incompletos", "Por favor completa todos los campos antes de reservar.", "warning");
            return;
        }
        if (horaInicio >= horaFin) {
            Swal.fire("⚠️ Error en horas", "La hora de inicio debe ser menor a la hora de fin.", "error");
            return;
        }

        Swal.fire({
            title: "¿Confirmar reserva?",
            text: "Se registrará la reserva con los datos seleccionados.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sí, reservar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33"
        }).then((result) => {
            if (result.isConfirmed) {
                formReserva.submit();
            }
        });
    });

    // Confirmación al cancelar reservas
    document.querySelectorAll(".cancelar-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            const formCancelar = e.target.closest("form");
            Swal.fire({
                title: "¿Cancelar reserva?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, cancelar",
                cancelButtonText: "No",
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6"
            }).then((result) => {
                if (result.isConfirmed) {
                    formCancelar.submit();
                }
            });
        });
    });
});
