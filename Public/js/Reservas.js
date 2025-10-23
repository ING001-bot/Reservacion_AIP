document.addEventListener('DOMContentLoaded', () => {
    const fechaInput = document.getElementById('fecha-select');
    const aulaSelect = document.getElementById('aula-select');
    const cuadroHoras = document.getElementById('cuadro-horas');
    const fechaBadge = document.getElementById('fecha-badge');
    const btnReservar = document.getElementById('btn-reservar');
    const formReserva = document.getElementById('form-reserva');
    const horaInicioInput = formReserva.querySelector("[name='hora_inicio']");
    const horaFinInput = formReserva.querySelector("[name='hora_fin']");
    const btnTurnoManana = document.getElementById('btn-turno-manana');
    const btnTurnoTarde = document.getElementById('btn-turno-tarde');
    const btnLimpiarSel = document.getElementById('btn-limpiar-seleccion');
    const txtInicio = document.getElementById('txt-inicio');
    const txtFin = document.getElementById('txt-fin');

    // Estado de turno y selección
    let turno = sessionStorage.getItem('turno_reserva') || 'manana';
    let selInicio = sessionStorage.getItem('sel_inicio') || '';
    let selFin = sessionStorage.getItem('sel_fin') || '';

    function actualizarHoras() {
        const fecha = fechaInput.value;
        const aula = aulaSelect.value;
        if (!fecha || !aula) {
            cuadroHoras.innerHTML = "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
            return;
        }
        fechaBadge.textContent = fecha;
        fetch(`actualizar_horas.php?id_aula=${encodeURIComponent(aula)}&fecha=${encodeURIComponent(fecha)}&turno=${encodeURIComponent(turno)}`)
            .then(res => res.ok ? res.text() : Promise.reject(new Error('No se pudo cargar disponibilidad')))
            .then(html => {
                cuadroHoras.innerHTML = html;
                // Restaurar resaltado si aplica
                aplicarResaltado();
            })
            .catch(() => {
                // Si no existe el endpoint, dejamos el contenido actual o mensaje
                if (!cuadroHoras.innerHTML.trim()) {
                    cuadroHoras.innerHTML = "<small class='text-muted'>No se pudo cargar disponibilidad. Ingresa horas manualmente.</small>";
                }
            });
    }

    // Inicializar botones de turno
    function actualizarBotonesTurno() {
        if (btnTurnoManana && btnTurnoTarde) {
            btnTurnoManana.classList.toggle('active', turno === 'manana');
            btnTurnoTarde.classList.toggle('active', turno === 'tarde');
        }
    }

    actualizarBotonesTurno();
    actualizarHoras();

    fechaInput.addEventListener('change', actualizarHoras);
    aulaSelect.addEventListener('change', actualizarHoras);

    // Selección de franja horaria: botones individuales HH:MM
    cuadroHoras.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        if (btn.classList.contains('btn-danger')) return; // ocupada
        const t = (btn.getAttribute('data-time') || btn.textContent.trim()); // HH:MM
        if (!/^\d{2}:\d{2}$/.test(t)) return;

        // Primer click: establece inicio; segundo click: fin (>= inicio)
        if (!selInicio || (selInicio && selFin)) {
            selInicio = t;
            selFin = '';
        } else {
            // Validar que fin sea >= inicio y múltiplos de 45m implícitos por la grilla
            if (compararHora(t, selInicio) < 0) {
                // si se hace click en un bloque previo, reinicia inicio
                selInicio = t;
                selFin = '';
            } else {
                // Verificar continuidad sin bloques ocupados entre inicio y t
                const lista = obtenerTiemposDisponiblesOrdenados();
                const idxIni = lista.findIndex(x => x.time === selInicio);
                const idxEnd = lista.findIndex(x => x.time === t);
                if (idxIni === -1 || idxEnd === -1 || idxEnd < idxIni) {
                    selInicio = t; selFin = '';
                } else {
                    const hayOcupada = lista.slice(idxIni, idxEnd + 1).some(x => x.danger);
                    if (hayOcupada) {
                        // No permitir saltar sobre ocupadas
                        selInicio = t; selFin = '';
                    } else {
                        selFin = t;
                    }
                }
            }
        }

        // Persistir temporalmente
        sessionStorage.setItem('sel_inicio', selInicio);
        sessionStorage.setItem('sel_fin', selFin);

        aplicarResaltado();
    });

    function compararHora(a, b) {
        // a y b en formato HH:MM
        return a.localeCompare(b);
    }

    function aplicarResaltado() {
        // Actualizar inputs y texto
        horaInicioInput.value = selInicio || '';
        horaFinInput.value = selFin || '';
        if (txtInicio) txtInicio.textContent = selInicio || '—';
        if (txtFin) txtFin.textContent = selFin || '—';

        // Quitar resaltado previo
        cuadroHoras.querySelectorAll('button.btn-success').forEach(b => b.classList.remove('active', 'btn-outline-primary'));

        if (!selInicio) return;
        const lista = obtenerTiemposDisponiblesOrdenados();
        if (!selFin) {
            // Solo marcar el inicio
            const item = lista.find(x => x.time === selInicio);
            if (item && item.el && !item.danger) item.el.classList.add('active', 'btn-outline-primary');
            return;
        }
        for (const x of lista) {
            if (compararHora(x.time, selInicio) >= 0 && compararHora(x.time, selFin) <= 0 && !x.danger) {
                x.el.classList.add('active', 'btn-outline-primary');
            }
        }
    }

    function obtenerTiemposDisponiblesOrdenados() {
        const nodes = Array.from(cuadroHoras.querySelectorAll('button'));
        const arr = nodes.map(el => ({
            time: el.getAttribute('data-time') || el.textContent.trim(),
            danger: el.classList.contains('btn-danger'),
            el
        })).filter(x => /^\d{2}:\d{2}$/.test(x.time));
        arr.sort((a,b) => a.time.localeCompare(b.time));
        return arr;
    }

    // Botones de turno
    if (btnTurnoManana) btnTurnoManana.addEventListener('click', () => {
        turno = 'manana';
        sessionStorage.setItem('turno_reserva', turno);
        actualizarBotonesTurno();
        actualizarHoras();
    });
    if (btnTurnoTarde) btnTurnoTarde.addEventListener('click', () => {
        turno = 'tarde';
        sessionStorage.setItem('turno_reserva', turno);
        actualizarBotonesTurno();
        actualizarHoras();
    });

    // Limpiar selección
    if (btnLimpiarSel) btnLimpiarSel.addEventListener('click', () => {
        selInicio = '';
        selFin = '';
        sessionStorage.removeItem('sel_inicio');
        sessionStorage.removeItem('sel_fin');
        aplicarResaltado();
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

        // Validar que la fecha sea al menos 1 día después de hoy
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const mañana = new Date(hoy);
        mañana.setDate(mañana.getDate() + 1);
        const fechaSeleccionada = new Date(fecha + 'T00:00:00');
        
        if (fechaSeleccionada < mañana) {
            Swal.fire("⚠️ Fecha no permitida", "Solo puedes reservar a partir del día siguiente. Las reservas deben hacerse con anticipación.", "error");
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
