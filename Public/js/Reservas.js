document.addEventListener('DOMContentLoaded', () => {
    const fechaInput = document.getElementById('fecha-select');
    const aulaSelect = document.getElementById('aula-select');
    const cuadroHoras = document.getElementById('cuadro-horas');
    const fechaBadge = document.getElementById('fecha-badge');
    const btnReservar = document.getElementById('btn-reservar');
    const formReserva = document.getElementById('form-reserva');

    // Función para actualizar disponibilidad de horas
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

    // Ejecutar al cargar la página
    actualizarHoras();

    // Detectar cambios
    fechaInput.addEventListener('change', actualizarHoras);
    aulaSelect.addEventListener('change', actualizarHoras);

    // Confirmación al reservar
    btnReservar.addEventListener('click', () => {
        const aula = formReserva.querySelector("[name='id_aula']").value;
        const fecha = formReserva.querySelector("[name='fecha']").value;
        const horaInicio = formReserva.querySelector("[name='hora_inicio']").value;
        const horaFin = formReserva.querySelector("[name='hora_fin']").value;

        // Validación de campos
        if (!aula || !fecha || !horaInicio || !horaFin) {
            alert("⚠️ Por favor completa todos los campos antes de reservar.");
            return;
        }

        if (horaInicio >= horaFin) {
            alert("⚠️ La hora de inicio debe ser menor a la hora de fin.");
            return;
        }

        // Confirmación
        if (confirm("¿Seguro que deseas realizar esta reserva?")) {
            formReserva.submit();
        }
    });

    // Confirmación al cancelar reservas
    document.querySelectorAll(".cancelar-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            const formCancelar = e.target.closest("form");
            if (confirm("⚠️ ¿Seguro que deseas cancelar esta reserva?")) {
                formCancelar.submit();
            }
        });
    });
});
