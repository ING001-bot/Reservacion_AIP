document.addEventListener('DOMContentLoaded', () => {
    const fechaInput = document.getElementById('fecha-select');
    const aulaSelect = document.getElementById('aula-select');
    const cuadroHoras = document.getElementById('cuadro-horas');
    const fechaBadge = document.getElementById('fecha-badge');
    const btnReservar = document.getElementById('btn-reservar');
    const formReserva = document.getElementById('form-reserva');

    // Función para actualizar disponibilidad de horas (tu código existente)
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

    fechaInput.addEventListener('change', actualizarHoras);
    aulaSelect.addEventListener('change', actualizarHoras);

    actualizarHoras();

    // -----------------------------
    // Confirmación con SweetAlert2
    // -----------------------------
    btnReservar.addEventListener('click', () => {
        const aula = formReserva.querySelector("[name='id_aula']").value;
        const fecha = formReserva.querySelector("[name='fecha']").value;
        const horaInicio = formReserva.querySelector("[name='hora_inicio']").value;
        const horaFin = formReserva.querySelector("[name='hora_fin']").value;

        // Validación de campos
        if (!aula || !fecha || !horaInicio || !horaFin) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor completa todos los campos antes de reservar',
                confirmButtonColor: '#25D366'
            });
            return;
        }

        if (horaInicio >= horaFin) {
            Swal.fire({
                icon: 'error',
                title: 'Error en horario',
                text: 'La hora de inicio debe ser menor a la hora de fin',
                confirmButtonColor: '#ff3b6c'
            });
            return;
        }

        // Confirmación antes de enviar
        Swal.fire({
            title: '¿Deseas realizar la reserva?',
            text: `${fecha} - ${horaInicio} a ${horaFin}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reservar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#25D366',
            cancelButtonColor: '#ff3b6c'
        }).then((result) => {
            if (result.isConfirmed) {
                formReserva.submit();
            }
        });
    });

    // Confirmación al cancelar reserva
    document.querySelectorAll(".cancelar-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            const formCancelar = e.target.closest("form");
            Swal.fire({
                title: '¿Cancelar esta reserva?',
                text: 'El aula quedará disponible nuevamente',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#ff3b6c',
                cancelButtonColor: '#25D366'
            }).then((result) => {
                if (result.isConfirmed) {
                    formCancelar.submit();
                }
            });
        });
    });
});
