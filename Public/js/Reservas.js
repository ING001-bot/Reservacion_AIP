(function(){
function init(){
    const fechaInput = document.getElementById('fecha-select');
    const aulaSelect = document.getElementById('aula-select');
    const cuadroHoras = document.getElementById('cuadro-horas');
    const fechaBadge = document.getElementById('fecha-badge');
    const btnReservar = document.getElementById('btn-reservar');
    const formReserva = document.getElementById('form-reserva');
    const horaInicioInput = formReserva ? formReserva.querySelector("[name='hora_inicio']") : null;
    const horaFinInput = formReserva ? formReserva.querySelector("[name='hora_fin']") : null;
    const btnTurnoManana = document.getElementById('btn-turno-manana');
    const btnTurnoTarde = document.getElementById('btn-turno-tarde');
    const btnLimpiarSel = document.getElementById('btn-limpiar-seleccion');
    const txtInicio = document.getElementById('txt-inicio');
    const txtFin = document.getElementById('txt-fin');

    // Estado de turno y selección
    let turno = sessionStorage.getItem('turno_reserva') || 'manana';
    let selInicio = sessionStorage.getItem('sel_inicio') || '';
    let selFin = sessionStorage.getItem('sel_fin') || '';
    let allowSubmit = false; // Flag para permitir el envío programático
    let userTriggered = false; // Marca si la acción viene del usuario (click/teclado)

    // Actualizar disponibilidad cuando cambie el aula
    if (aulaSelect) {
        aulaSelect.addEventListener('change', () => {
            console.log('Aula cambiada a:', aulaSelect.value);
            // Limpiar selección de horas al cambiar de aula
            selInicio = '';
            selFin = '';
            sessionStorage.removeItem('sel_inicio');
            sessionStorage.removeItem('sel_fin');
            if (horaInicioInput) horaInicioInput.value = '';
            if (horaFinInput) horaFinInput.value = '';
            if (txtInicio) txtInicio.textContent = '—';
            if (txtFin) txtFin.textContent = '—';
            
            // Actualizar disponibilidad de horas
            actualizarHoras();
        });
    }

    // Actualizar disponibilidad cuando cambie la fecha
    if (fechaInput) {
        fechaInput.addEventListener('change', () => {
            console.log('Fecha cambiada a:', fechaInput.value);
            // Limpiar selección de horas al cambiar de fecha
            selInicio = '';
            selFin = '';
            sessionStorage.removeItem('sel_inicio');
            sessionStorage.removeItem('sel_fin');
            if (horaInicioInput) horaInicioInput.value = '';
            if (horaFinInput) horaFinInput.value = '';
            if (txtInicio) txtInicio.textContent = '—';
            if (txtFin) txtFin.textContent = '—';
            
            // Actualizar disponibilidad de horas
            actualizarHoras();
        });
    }

    // Estado inicial: reflejar turno y cargar disponibilidad apenas se entra
    actualizarBotonesTurno();
    actualizarHoras();

    // Cargar botones desde el servidor (HTML)
    function bindCuadroHoras() {
        if (!cuadroHoras) return;
        const botones = cuadroHoras.querySelectorAll('button[data-time]');
        botones.forEach(b => {
            b.removeEventListener('click', manejarClickHora);
            b.addEventListener('click', manejarClickHora);
        });
    }

    function actualizarHoras() {
        const fecha = fechaInput ? fechaInput.value : '';
        const aula = aulaSelect ? aulaSelect.value : '';

        console.log('actualizarHoras() llamado - Aula:', aula, 'Fecha:', fecha, 'Turno:', turno);
        
        if (!fecha || !aula) {
            if (cuadroHoras) {
                cuadroHoras.innerHTML = "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
            }
            return;
        }
        
        if (fechaBadge) fechaBadge.textContent = fecha;
        
        // Mostrar indicador de carga
        if (cuadroHoras) {
            cuadroHoras.innerHTML = "<div class='text-center'><div class='spinner-border spinner-border-sm text-primary' role='status'><span class='visually-hidden'>Cargando...</span></div> Actualizando disponibilidad...</div>";
        }
        
        // Cargar HTML con los botones y estados
        const url = `Actualizar_horas.php?id_aula=${encodeURIComponent(aula)}&fecha=${encodeURIComponent(fecha)}&turno=${encodeURIComponent(turno)}`;
        console.log('Fetch URL:', url);
        
        fetch(url)
            .then(res => {
                console.log('Response status:', res.status, res.ok);
                return res.ok ? res.text() : Promise.reject('No se pudo cargar disponibilidad');
            })
            .then(html => {
                console.log('HTML recibido, longitud:', html.length);
                cuadroHoras.innerHTML = html;
                bindCuadroHoras();
                aplicarResaltado(false);
            })
            .catch(err => {
                console.error('Error en actualizarHoras:', err);
                if (cuadroHoras && !cuadroHoras.innerHTML.trim()) {
                    cuadroHoras.innerHTML = "<small class='text-muted'>No se pudo cargar disponibilidad. Ingresa horas manualmente.</small>";
                }
            });
        
        // Actualizar estado de los botones de turno
        actualizarBotonesTurno();
    }

    function actualizarBotonesTurno() {
        if (btnTurnoManana) btnTurnoManana.classList.toggle('active', turno === 'manana');
        if (btnTurnoTarde) btnTurnoTarde.classList.toggle('active', turno === 'tarde');
    }

    // Configurar manejadores de eventos para los botones de hora
    function configurarBotonesHoras() {
        const botonesHora = document.querySelectorAll('#cuadro-horas button[data-time]');
        botonesHora.forEach(boton => {
            // Eliminar eventos anteriores para evitar duplicados
            boton.removeEventListener('click', manejarClickHora);
            // Agregar nuevo manejador
            boton.addEventListener('click', manejarClickHora);
        });
    }

    // Manejador de clic en los botones de hora
    function manejarClickHora(e) {
        const btn = e.target.closest('button');
        if (!btn || btn.disabled || btn.classList.contains('btn-danger')) return;
        
        const t = btn.dataset.time || btn.textContent.trim();
        if (!/^\d{1,2}:\d{2}(-\d{1,2}:\d{2})?$/.test(t)) return;

        // Si ya hay una selección completa o no hay inicio, comenzar una nueva
        if (!selInicio || (selInicio && selFin)) {
            selInicio = t;
            selFin = '';
            
            // Actualizar campo de inicio en el formulario
            if (horaInicioInput) horaInicioInput.value = t;
            if (txtInicio) txtInicio.textContent = t;
            
            // Limpiar fin de selección anterior
            if (horaFinInput) horaFinInput.value = '';
            if (txtFin) txtFin.textContent = '—';
            
            // Guardar en sessionStorage
            sessionStorage.setItem('sel_inicio', selInicio);
            sessionStorage.removeItem('sel_fin');
            userTriggered = true;
        } 
        // Si hay un inicio pero no un fin, establecer el fin si es válido
        else if (selInicio && !selFin) {
            // Verificar que no se esté seleccionando un horario anterior al inicio
            if (compararHora(t, selInicio) <= 0) {
                // Si se hace clic en un horario anterior al inicio, reiniciar selección
                selInicio = t;
                selFin = '';
                
                // Actualizar campo de inicio
                if (horaInicioInput) horaInicioInput.value = t;
                if (txtInicio) txtInicio.textContent = t;
                
                // Limpiar fin
                if (horaFinInput) horaFinInput.value = '';
                if (txtFin) txtFin.textContent = '—';
                
                // Guardar en sessionStorage
                sessionStorage.setItem('sel_inicio', selInicio);
                sessionStorage.removeItem('sel_fin');
                userTriggered = true;
            } else {
                // Verificar que no haya horarios ocupados en el rango
                const lista = obtenerTiemposDisponiblesOrdenados();
                const idxIni = lista.findIndex(x => x.time === selInicio);
                const idxEnd = lista.findIndex(x => x.time === t);
                
                if (idxIni !== -1 && idxEnd !== -1 && idxEnd > idxIni) {
                    // Verificar si hay horarios ocupados o recreo en el rango
                    const rango = lista.slice(idxIni, idxEnd + 1);
                    const hayOcupados = rango.some(x => x.danger || x.disabled || x.el.disabled || x.el.classList.contains('btn-warning'));
                    
                    if (!hayOcupados) {
                        selFin = t;
                    } else {
                        // Mostrar mensaje de error y mantener el inicio seleccionado
                        Swal.fire({
                            title: 'Horario no disponible',
                            text: 'No puedes seleccionar un rango que incluya horarios ocupados o de recreo.',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                        selFin = '';
                    }
                } else {
                    // Si no es un rango válido, establecer como nuevo inicio
                    selInicio = t;
                }
            }
        } 
        // Si no hay inicio, establecer el inicio
        else {
            selInicio = t;
            selFin = '';
        }

        // Actualizar la interfaz
        sessionStorage.setItem('sel_inicio', selInicio);
        sessionStorage.setItem('sel_fin', selFin);
        userTriggered = true;
        aplicarResaltado(true);
        
        // Actualizar los campos de formulario
        if (horaInicioInput) horaInicioInput.value = selInicio || '';
        if (horaFinInput) horaFinInput.value = selFin || '';
    }

    // Función para convertir hora en formato HH:MM a minutos
    function horaAMinutos(horaStr) {
        if (!horaStr) return 0;
        const [h, m] = horaStr.split(':').map(Number);
        return h * 60 + (m || 0);
    }

    // Función para verificar si un horario está dentro de un rango
    function estaEnRango(hora, inicio, fin) {
        if (!hora || !inicio || !fin) return false;
        
        const minutosHora = horaAMinutos(hora);
        const minutosInicio = horaAMinutos(inicio);
        const minutosFin = horaAMinutos(fin);
        
        return minutosHora >= minutosInicio && minutosHora <= minutosFin;
    }

    // Comparar hora en formato HH:MM. Devuelve -1 si a<b, 0 si igual, 1 si a>b
    function compararHora(a, b) {
        if (!a || !b) return 0;
        const [ah, am] = a.split(':').map(Number);
        const [bh, bm] = b.split(':').map(Number);
        if (ah !== bh) return ah < bh ? -1 : 1;
        if (am !== bm) return am < bm ? -1 : 1;
        return 0;
    }

    function aplicarResaltado(fromUser = false) {
        // Actualizar inputs y texto
        if (horaInicioInput && !horaInicioInput.value) horaInicioInput.value = selInicio || '';
        if (horaFinInput && !horaFinInput.value) horaFinInput.value = selFin || '';
        if (txtInicio) txtInicio.textContent = selInicio || '—';
        if (txtFin) txtFin.textContent = selFin || '—';

        // Quitar resaltado previo
        if (cuadroHoras) {
            const botones = cuadroHoras.querySelectorAll('button');
            
            botones.forEach(btn => {
                // No tocar botones deshabilitados (recreo o marcadores) ni ocupados
                if (btn.disabled) return;
                btn.classList.remove('active', 'btn-outline-primary', 'btn-half-primary');
                if (!btn.classList.contains('btn-danger')) {
                    btn.classList.add('btn-success');
                }
            });

            if (!selInicio) return;
            
            const lista = obtenerTiemposDisponiblesOrdenados();
            
            // Si solo hay inicio seleccionado
            if (!selFin) {
                const item = lista.find(x => x.time === selInicio);
                if (item && item.el && !item.danger) {
                    item.el.classList.add('active', 'btn-outline-primary');
                    item.el.classList.remove('btn-success');
                }
                return;
            }

            // Si hay inicio y fin seleccionados
            const hStart = selInicio.split('-')[0];
            const hEnd = selFin.split('-')[0];
            const start = compararHora(hStart, hEnd) <= 0 ? hStart : hEnd;
            const end   = compararHora(hStart, hEnd) <= 0 ? hEnd   : hStart;

            // Construir subconjunto por valor de hora (nunca incluir anteriores al inicio)
            const rango = lista.filter(x => {
                const hx = (x.time || '').split('-')[0];
                return compararHora(hx, start) >= 0 && compararHora(hx, end) <= 0;
            });

            if (!rango.length) return;

            // Verificar si hay horarios ocupados o especiales en el rango
            const hayOcupados = rango.some(x => x.danger || x.disabled || x.el.disabled || x.el.classList.contains('btn-warning'));

            if (hayOcupados) {
                if (fromUser) {
                    Swal.fire({
                        title: 'Horario no disponible',
                        text: 'No puedes seleccionar un rango que incluya horarios ocupados o de recreo.',
                        icon: 'warning',
                        confirmButtonText: 'Entendido'
                    });
                }
                // No pintar ni alterar si no viene del usuario
                return;
            }

            // Resaltar el rango seleccionado (inclusivo)
            rango.forEach(x => {
                if (x && !x.danger && !x.disabled && !x.el.disabled && !x.el.classList.contains('btn-warning')) {
                    x.el.classList.add('active', 'btn-outline-primary');
                    x.el.classList.remove('btn-success');
                }
            });

            // Pintar medio botón en el fin si NO es fin especial (para indicar que después puede iniciar otra reserva)
            const finesEspeciales = ['10:10','12:45','16:00','18:35'];
            const finItem = rango[rango.length - 1];
            if (finItem && !finesEspeciales.includes(finItem.time)) {
                finItem.el.classList.add('btn-half-primary');
            }
        }
    }

    function obtenerTiemposDisponiblesOrdenados() {
        if (!cuadroHoras) return [];
        
        const nodes = Array.from(cuadroHoras.querySelectorAll('button'));
        const arr = nodes
            .map(el => ({
                time: el.dataset.time || el.textContent.trim(),
                danger: el.classList.contains('btn-danger'),
                disabled: el.disabled,
                el
            }))
            .filter(x => /^\d{1,2}:\d{2}(-\d{1,2}:\d{2})?$/.test(x.time));
            
        // Ordenar por hora
        arr.sort((a, b) => {
            // Extraer solo la primera parte de la hora para la comparación
            const horaA = a.time.split('-')[0];
            const horaB = b.time.split('-')[0];
            return horaA.localeCompare(horaB);
        });
        
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
        // Limpiar inputs del formulario y textos visibles
        if (horaInicioInput) horaInicioInput.value = '';
        if (horaFinInput) horaFinInput.value = '';
        if (txtInicio) txtInicio.textContent = '—';
        if (txtFin) txtFin.textContent = '—';
        aplicarResaltado();
    });

    function confirmarYEnviar() {
        if (!formReserva) return;
        const aulaEl = formReserva.querySelector("[name='id_aula']");
        const fechaEl = formReserva.querySelector("[name='fecha']");
        const aula = aulaEl ? aulaEl.value : '';
        const fecha = fechaEl ? fechaEl.value : '';
        const horaInicio = horaInicioInput ? horaInicioInput.value : '';
        const horaFin = horaFinInput ? horaFinInput.value : '';

        if (!aula || !fecha || !horaInicio || !horaFin) {
            Swal.fire("⚠️ Campos incompletos", "Por favor completa todos los campos antes de reservar.", "warning");
            return;
        }

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
            confirmButtonColor: "#16a34a",
            cancelButtonColor: "#1e6bd6"
        }).then((result) => {
            if (result.isConfirmed) {
                if (btnReservar) btnReservar.disabled = true;
                allowSubmit = true;
                formReserva.submit();
            }
        });
    }
    // Confirmación al reservar con click del botón
    if (btnReservar) btnReservar.addEventListener('click', (e) => {
        e.preventDefault();
        confirmarYEnviar();
    });
    // Interceptar submit del formulario (Enter o envíos programáticos)
    if (formReserva) formReserva.addEventListener('submit', (e) => {
        if (!allowSubmit) {
            e.preventDefault();
            confirmarYEnviar();
        }
    }, true); // usar captura para asegurar bloqueo temprano

    // Forzar confirmación también con Enter en cualquier campo del formulario
    if (formReserva) formReserva.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmarYEnviar();
        }
    });

    // Cancelar con motivo
    document.querySelectorAll(".cancelar-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            // Si está deshabilitado por ventana de tiempo, avisar y salir
            if (btn.classList.contains('disabled') || btn.hasAttribute('disabled')) {
                Swal.fire({ icon:'info', title:'La reserva ya ha pasado', text:'Las cancelaciones solo se permiten hasta 1 hora antes del inicio.' });
                return;
            }
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
}

// Inicializar la aplicación cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
})(); 
