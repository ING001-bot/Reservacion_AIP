document.addEventListener('DOMContentLoaded', () => {
    const fechaInput = document.getElementById('fecha-select');
    const aulaSelect = document.getElementById('aula-select');
    const cuadroHoras = document.getElementById('cuadro-horas');
    const fechaBadge = document.getElementById('fecha-badge');
    const btnReservar = document.getElementById('btn-reservar');
    const formReserva = document.getElementById('form-reserva');
    const horaInicioInput = formReserva.querySelector("[name='hora_inicio']");
    const horaFinInput = formReserva.querySelector("[name='hora_fin']");

    function actualizarHoras() {
        const fecha = fechaInput.value;
        const aula = aulaSelect.value;
        if (!fecha || !aula) {
            cuadroHoras.innerHTML = "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
            return;
        }
        fechaBadge.textContent = fecha;
        fetch(`actualizar_horas.php?id_aula=${encodeURIComponent(aula)}&fecha=${encodeURIComponent(fecha)}`)
            .then(res => res.ok ? res.text() : Promise.reject(new Error('No se pudo cargar disponibilidad')))
            .then(html => {
                cuadroHoras.innerHTML = html;
            })
            .catch(() => {
                // Si no existe el endpoint, dejamos el contenido actual o mensaje
                if (!cuadroHoras.innerHTML.trim()) {
                    cuadroHoras.innerHTML = "<small class='text-muted'>No se pudo cargar disponibilidad. Ingresa horas manualmente.</small>";
                }
            });
    }

    actualizarHoras();

    fechaInput.addEventListener('change', actualizarHoras);
    aulaSelect.addEventListener('change', actualizarHoras);

    // Selección de franja horaria: auto-rellena los campos de hora
    cuadroHoras.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        if (btn.classList.contains('btn-danger')) return; // ocupada
        const txt = btn.textContent.trim(); // formato "HH:MM - HH:MM"
        const m = txt.match(/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/);
        if (m) {
            horaInicioInput.value = m[1];
            horaFinInput.value = m[2];
            // Marcar selección visual
            cuadroHoras.querySelectorAll('button.btn-success').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    });

    // Confirmación al reservar
    btnReservar.addEventListener('click', () => {
        const aula = formReserva.querySelector("[name='id_aula']").value;
        const fecha = formReserva.querySelector("[name='fecha']").value;
        const horaInicio = horaInicioInput.value;
        const horaFin = horaFinInput.value;

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
                btnReservar.disabled = true;
                formReserva.submit();
            }
        });
    });

    // Cancelar con motivo
    document.querySelectorAll(".cancelar-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            const formCancelar = e.target.closest("form");
            Swal.fire({
                title: "Cancelar reserva",
                html: '<p class="mb-2">Indica el motivo de la cancelación (mínimo 10 caracteres):</p>' +
                      '<textarea id="swal-motivo" class="swal2-textarea" placeholder="Describe el motivo" style="height: 120px;"></textarea>',
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Confirmar cancelación",
                cancelButtonText: "Volver",
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                focusConfirm: false,
                preConfirm: () => {
                    const val = (document.getElementById('swal-motivo')?.value || '').trim();
                    if (val.length < 10) {
                        Swal.showValidationMessage('El motivo debe tener al menos 10 caracteres.');
                        return false;
                    }
                    return val;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Adjuntar motivo al form
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'motivo';
                    hidden.value = result.value;
                    formCancelar.appendChild(hidden);
                    formCancelar.submit();
                }
            });
        });
    });
});
