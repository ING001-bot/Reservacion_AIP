<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../Public/index.php');
    exit;
}

require_once '../config/conexion.php';

$id_usuario = (int)$_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo'] ?? '';

// Obtener todas las notificaciones con información adicional
$stmt = $conexion->prepare("
    SELECT 
        n.id_notificacion, 
        n.titulo, 
        n.mensaje, 
        n.url, 
        n.leida, 
        n.creada_en,
        n.metadata
    FROM notificaciones n
    WHERE n.id_usuario = ? 
    ORDER BY n.creada_en DESC 
    LIMIT 100
");
$stmt->execute([$id_usuario]);
$todasNotificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Enriquecer notificaciones con datos relacionados
foreach ($todasNotificaciones as &$notif) {
    $metadata = json_decode($notif['metadata'] ?? '{}', true);
    $notif['detalles'] = [];
    
    // Debug: registrar metadata
    error_log("Notificación ID: " . $notif['id_notificacion'] . " - Metadata: " . ($notif['metadata'] ?? 'NULL'));
    
    // Si es una notificación de préstamo/devolución
    if (isset($metadata['id_prestamo'])) {
        error_log("Buscando detalles de préstamo ID: " . $metadata['id_prestamo']);
        
        // Primero obtenemos los datos básicos del préstamo
        $stmtPrestamo = $conexion->prepare("
            SELECT 
                p.id_prestamo,
                p.fecha_prestamo,
                p.hora_inicio,
                p.hora_fin,
                p.comentario_devolucion as observacion,
                p.estado,
                p.id_usuario,
                p.id_aula,
                u.nombre as solicitante,
                u.correo as correo_solicitante,
                a.nombre_aula as aula
            FROM prestamos p
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            LEFT JOIN aulas a ON p.id_aula = a.id_aula
            WHERE p.id_prestamo = ?
        ");
        $stmtPrestamo->execute([$metadata['id_prestamo']]);
        $notif['detalles'] = $stmtPrestamo->fetch(PDO::FETCH_ASSOC) ?: [];
        
        // Ahora buscamos TODOS los equipos del mismo "pack" (mismo usuario, aula, fecha, hora)
        if (!empty($notif['detalles'])) {
            $stmtEquipos = $conexion->prepare("
                SELECT GROUP_CONCAT(e.nombre_equipo SEPARATOR ', ') as equipos
                FROM prestamos p
                LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                WHERE p.id_usuario = ?
                AND p.id_aula = ?
                AND p.fecha_prestamo = ?
                AND p.hora_inicio = ?
                AND (p.hora_fin = ? OR (p.hora_fin IS NULL AND ? IS NULL))
            ");
            $stmtEquipos->execute([
                $notif['detalles']['id_usuario'],
                $notif['detalles']['id_aula'],
                $notif['detalles']['fecha_prestamo'],
                $notif['detalles']['hora_inicio'],
                $notif['detalles']['hora_fin'],
                $notif['detalles']['hora_fin']
            ]);
            $equiposResult = $stmtEquipos->fetch(PDO::FETCH_ASSOC);
            $notif['detalles']['equipos'] = $equiposResult['equipos'] ?? 'Sin equipos';
        }
        
        error_log("Detalles encontrados: " . count($notif['detalles']) . " campos");
    }
    // Si es una notificación de reserva
    elseif (isset($metadata['id_reserva'])) {
        error_log("Buscando detalles de reserva ID: " . $metadata['id_reserva']);
        $stmtReserva = $conexion->prepare("
            SELECT 
                r.id_reserva,
                r.fecha,
                r.hora_inicio,
                r.hora_fin,
                u.nombre as solicitante,
                u.correo as correo_solicitante,
                a.nombre_aula as aula
            FROM reservas r
            LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
            LEFT JOIN aulas a ON r.id_aula = a.id_aula
            WHERE r.id_reserva = ?
        ");
        $stmtReserva->execute([$metadata['id_reserva']]);
        $notif['detalles'] = $stmtReserva->fetch(PDO::FETCH_ASSOC) ?: [];
        error_log("Detalles encontrados: " . count($notif['detalles']) . " campos");
    } elseif (isset($metadata['tipo']) && $metadata['tipo'] === 'cancelacion_reserva') {
        // Detalles desde metadata de cancelación (sin consultas adicionales)
        $notif['detalles'] = [
            'solicitante' => $metadata['solicitante'] ?? 'Usuario',
            'aula' => $metadata['aula'] ?? '',
            'fecha' => $metadata['fecha'] ?? '',
            'hora_inicio' => $metadata['hora_inicio'] ?? '',
            'hora_fin' => $metadata['hora_fin'] ?? '',
            'motivo' => $metadata['motivo'] ?? ''
        ];
    } else {
        error_log("Notificación sin metadata de préstamo o reserva");
    }
}
unset($notif);

// Separar por categorías
$reservas = [];
$equipos = [];
$devoluciones = [];
$cancelaciones = [];

foreach ($todasNotificaciones as $notif) {
    $titulo = strtolower($notif['titulo']);
    if (stripos($titulo, 'cancelación de reserva') !== false || stripos($titulo, 'cancelacion de reserva') !== false) {
        $cancelaciones[] = $notif;
    } elseif (stripos($titulo, 'reserva') !== false) {
        $reservas[] = $notif;
    } elseif (stripos($titulo, 'devolución') !== false || stripos($titulo, 'devolucion') !== false) {
        $devoluciones[] = $notif;
    } else {
        // Préstamos y demás van a equipos
        $equipos[] = $notif;
    }
}

// Si no es vista embebida, mostrar HTML completo
if (!defined('EMBEDDED_VIEW')):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Notificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body>
<?php include 'partials/navbar.php'; ?>
<?php endif; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bell me-2"></i>Historial de Notificaciones</h2>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-4" id="notifTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="reservas-tab" data-bs-toggle="tab" data-bs-target="#reservas" type="button">
                📅 Reservas <span class="badge bg-primary"><?= count($reservas) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelaciones-tab" data-bs-toggle="tab" data-bs-target="#cancelaciones" type="button">
                ⛔ Cancelaciones <span class="badge bg-primary"><?= count($cancelaciones) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="equipos-tab" data-bs-toggle="tab" data-bs-target="#equipos" type="button">
                💻 Préstamos <span class="badge bg-primary"><?= count($equipos) ?></span>
            </button>
        </li>
        <?php if ($tipo_usuario !== 'Encargado'): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="devoluciones-tab" data-bs-toggle="tab" data-bs-target="#devoluciones" type="button">
                🔄 Devoluciones <span class="badge bg-primary"><?= count($devoluciones) ?></span>
            </button>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Contenido de pestañas -->
    <div class="tab-content" id="notifTabContent">
        <!-- Reservas -->
        <div class="tab-pane fade show active" id="reservas" role="tabpanel">
            <?php if (empty($reservas)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No tienes notificaciones de reservas.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($reservas as $notif): 
                        echo renderNotificacion($notif);
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cancelaciones -->
        <div class="tab-pane fade" id="cancelaciones" role="tabpanel">
            <?php if (empty($cancelaciones)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No tienes notificaciones de cancelaciones.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($cancelaciones as $notif): 
                        echo renderNotificacion($notif);
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Equipos/Préstamos -->
        <div class="tab-pane fade" id="equipos" role="tabpanel">
            <?php if (empty($equipos)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No tienes notificaciones de préstamos.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($equipos as $notif): 
                        echo renderNotificacion($notif);
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Devoluciones (oculto para Encargado) -->
        <?php if ($tipo_usuario !== 'Encargado'): ?>
        <div class="tab-pane fade" id="devoluciones" role="tabpanel">
            <?php if (empty($devoluciones)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No tienes notificaciones de devoluciones.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($devoluciones as $notif): 
                        echo renderNotificacion($notif);
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Detalles -->
<div class="modal fade" id="detalleNotifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitulo">
                    <i class="fas fa-info-circle me-2"></i>Detalles de la Notificación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <style>
                /* Mejor UX: modal grande y con scroll interno controlado */
                #detalleNotifModal .modal-body{ max-height: calc(100vh - 220px); overflow:auto; }
                @media (max-width: 576px){
                    /* Bajar más el modal y evitar que la cabecera lo tape */
                    #detalleNotifModal .modal-dialog{ margin: 2.75rem 0.5rem 1rem; }
                    #detalleNotifModal .modal-dialog-centered{ align-items: flex-start !important; }
                    #detalleNotifModal .modal-header{ padding: .5rem .75rem; }
                    #detalleNotifModal .modal-body{ max-height: calc(100vh - 200px); padding: .75rem; }
                    #detalleNotifModal .modal-footer{ padding: .5rem .75rem; }
                    #detalleNotifModal .card .card-body{ padding: .75rem; }
                }
            </style>
            <div class="modal-body" id="modalContenido">
                <!-- Se llenará dinámicamente con JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="irAUrl">
                    <i class="fas fa-external-link-alt me-1"></i>Ir a la página
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Función para renderizar una notificación
function renderNotificacion($notif) {
    $leida = (int)$notif['leida'];
    $bgClass = $leida ? 'list-group-item-light' : 'list-group-item-primary';
    $url = $notif['url'] ?? '#';
    $detalles = $notif['detalles'] ?? [];
    $detallesJson = htmlspecialchars(json_encode($detalles), ENT_QUOTES, 'UTF-8');
    
    ob_start();
    ?>
    <?php 
      $esCancelacion = stripos(($notif['titulo'] ?? ''), 'Cancelación de reserva') !== false || stripos(($notif['titulo'] ?? ''), 'Cancelacion de reserva') !== false;
    ?>
    <div class="list-group-item list-group-item-action <?= $bgClass ?> d-flex align-items-start gap-3 notif-item"
       data-notif-id="<?= (int)$notif['id_notificacion'] ?>"
       data-notif-url="<?= htmlspecialchars($url) ?>"
       data-notif-titulo="<?= htmlspecialchars($notif['titulo']) ?>"
       data-notif-msg="<?= htmlspecialchars($notif['mensaje']) ?>"
       data-notif-detalles='<?= $detallesJson ?>'
       style="cursor:pointer;">
        <div class="pt-1" style="width:30px; text-align:center;">
            <i class="fas fa-bell fa-lg <?= $leida ? 'text-muted' : 'text-primary' ?>"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <strong class="<?= $leida ? 'text-muted' : '' ?>"><?= htmlspecialchars($notif['titulo']) ?></strong>
                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($notif['creada_en'])) ?></small>
            </div>
            <div class="<?= $leida ? 'text-muted' : '' ?> mb-2"><?= htmlspecialchars($notif['mensaje']) ?></div>
            <div class="d-flex gap-2 align-items-center">
                <?php if (!$leida): ?>
                    <span class="badge bg-primary">Nueva</span>
                <?php endif; ?>
                <?php if (!empty($detalles) || $esCancelacion): ?>
                    <button class="btn btn-sm btn-outline-info ver-detalles" type="button">
                        <i class="fas fa-search-plus me-1"></i>Ver más detalles
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<?php if (!defined('EMBEDDED_VIEW')): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando página de Notificaciones');
    
    // Verificar que Bootstrap esté cargado
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap no está cargado');
        return;
    }
    
    // Modal de detalles
    const modalElement = document.getElementById('detalleNotifModal');
    if (!modalElement) {
        console.error('❌ No se encontró el elemento del modal');
        return;
    }
    
    const modalDetalles = new bootstrap.Modal(modalElement);
    // Asegurar accesibilidad y evitar warning de aria-hidden con foco retenido
    modalElement.setAttribute('tabindex','-1');
    modalElement.addEventListener('shown.bs.modal', () => {
        try { modalElement.focus(); } catch (e) {}
    });
    function safeShowModal(){
        try {
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }
        } catch (e) {}
        // Deferir al siguiente frame para no abrir mientras el DOM aún aplica aria-hidden
        requestAnimationFrame(() => { setTimeout(() => { try { modalDetalles.show(); } catch(e){} }, 0); });
    }
    let urlActual = '#';
    
    console.log('✅ Modal inicializado correctamente');

    // Función para mostrar detalles en el modal
    window.mostrarDetalles = function(titulo, detalles, url, notifId) {
        console.log('🔍 mostrarDetalles llamado con:', {titulo, detalles, url, notifId});
    urlActual = url;
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-info-circle me-2"></i>' + titulo;
    
    let html = '';
    const esCancelacion = /cancelaci[óo]n de reserva/i.test(titulo || '');
    
    if (esCancelacion) {
        // Ocultar botón Ir
        const btnIr = document.getElementById('irAUrl');
        if (btnIr) btnIr.style.display = 'none';
        // Si tenemos detalles desde metadata, renderizar tarjetas como en reservas
        if (detalles && Object.keys(detalles).length > 0) {
            html += '<div class="row g-3">';
            if (detalles.solicitante) {
                html += '<div class="col-12"><div class="card bg-light"><div class="card-body">';
                html += '<h6 class="card-title"><i class="fas fa-user me-2"></i>Solicitante</h6>';
                html += '<p class="mb-0"><strong>Nombre:</strong> ' + detalles.solicitante + '</p>';
                html += '</div></div></div>';
            }
            if (detalles.aula) {
                html += '<div class="col-md-6"><div class="card border-primary"><div class="card-body">';
                html += '<h6 class="card-title"><i class="fas fa-door-open me-2"></i>Aula</h6>';
                html += '<p class="mb-0 fs-5 text-primary">' + detalles.aula + '</p>';
                html += '</div></div></div>';
            }
            if (detalles.fecha) {
                html += '<div class="col-md-6"><div class="card border-info"><div class="card-body">';
                html += '<h6 class="card-title"><i class="fas fa-calendar me-2"></i>Fecha y Hora</h6>';
                html += '<p class="mb-1"><strong>Fecha:</strong> ' + detalles.fecha + '</p>';
                if (detalles.hora_inicio && detalles.hora_fin) {
                    html += '<p class="mb-0"><strong>Horario:</strong> ' + detalles.hora_inicio + ' - ' + detalles.hora_fin + '</p>';
                }
                html += '</div></div></div>';
            }
            if (detalles.motivo) {
                html += '<div class="col-12"><div class="card border-warning"><div class="card-body">';
                html += '<h6 class="card-title"><i class="fas fa-ban me-2"></i>Motivo de cancelación</h6>';
                html += '<p class="mb-0">' + detalles.motivo + '</p>';
                html += '</div></div></div>';
            }
            html += '</div>';
        } else {
            // Fallback si no hay detalles (notificación antigua)
            html = '<div class="alert alert-warning"><i class="fas fa-ban me-2"></i>Esta es una notificación de cancelación de reserva.<br>Usa el historial para ver más información si la necesitas.</div>';
        }
    } else if (detalles && Object.keys(detalles).length > 0) {
        html += '<div class="row g-3">';
        
        // Información del solicitante
        if (detalles.solicitante) {
            html += '<div class="col-12">';
            html += '<div class="card bg-light">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-user me-2"></i>Solicitante</h6>';
            html += '<p class="mb-1"><strong>Nombre:</strong> ' + detalles.solicitante + '</p>';
            if (detalles.correo_solicitante) {
                html += '<p class="mb-0"><strong>Correo:</strong> ' + detalles.correo_solicitante + '</p>';
            }
            html += '</div></div></div>';
        }
        
        // Información de aula
        if (detalles.aula) {
            html += '<div class="col-md-6">';
            html += '<div class="card border-primary">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-door-open me-2"></i>Aula</h6>';
            html += '<p class="mb-0 fs-5 text-primary">' + detalles.aula + '</p>';
            html += '</div></div></div>';
        }
        
        // Información de fecha y hora
        if (detalles.fecha_prestamo || detalles.fecha) {
            html += '<div class="col-md-6">';
            html += '<div class="card border-info">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-calendar me-2"></i>Fecha y Hora</h6>';
            html += '<p class="mb-1"><strong>Fecha:</strong> ' + (detalles.fecha_prestamo || detalles.fecha) + '</p>';
            if (detalles.hora_inicio && detalles.hora_fin) {
                html += '<p class="mb-0"><strong>Horario:</strong> ' + detalles.hora_inicio + ' - ' + detalles.hora_fin + '</p>';
            }
            html += '</div></div></div>';
        }
        
        // Equipos prestados
        if (detalles.equipos) {
            html += '<div class="col-12">';
            html += '<div class="card border-success">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-laptop me-2"></i>Equipos</h6>';
            html += '<p class="mb-0">' + detalles.equipos + '</p>';
            html += '</div></div></div>';
        }
        
        // Estado
        if (detalles.estado) {
            let estadoClass = detalles.estado === 'Devuelto' ? 'success' : 
                             detalles.estado === 'Prestado' ? 'warning' : 'secondary';
            html += '<div class="col-md-6">';
            html += '<div class="card border-' + estadoClass + '">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-info-circle me-2"></i>Estado</h6>';
            html += '<span class="badge bg-' + estadoClass + ' fs-6">' + detalles.estado + '</span>';
            html += '</div></div></div>';
        }
        
        // Observaciones
        if (detalles.observacion || detalles.observaciones) {
            const obs = detalles.observacion || detalles.observaciones;
            if (obs && obs.trim()) {
                html += '<div class="col-12">';
                html += '<div class="card border-warning">';
                html += '<div class="card-body">';
                html += '<h6 class="card-title"><i class="fas fa-comment-dots me-2"></i>Observaciones</h6>';
                html += '<p class="mb-0 fst-italic">' + obs + '</p>';
                html += '</div></div></div>';
            }
        }
        
        // ID del préstamo/reserva
        if (detalles.id_prestamo) {
            html += '<div class="col-md-6">';
            html += '<div class="card bg-light">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-hashtag me-2"></i>ID Préstamo</h6>';
            html += '<p class="mb-0 fw-bold">#' + detalles.id_prestamo + '</p>';
            html += '</div></div></div>';
        } else if (detalles.id_reserva) {
            html += '<div class="col-md-6">';
            html += '<div class="card bg-light">';
            html += '<div class="card-body">';
            html += '<h6 class="card-title"><i class="fas fa-hashtag me-2"></i>ID Reserva</h6>';
            html += '<p class="mb-0 fw-bold">#' + detalles.id_reserva + '</p>';
            html += '</div></div></div>';
        }
        
        html += '</div>';
    } else {
        html = '<div class="alert alert-warning">';
        html += '<i class="fas fa-exclamation-triangle me-2"></i>';
        html += '<strong>Sin detalles disponibles</strong><br>';
        html += 'Esta notificación no tiene información adicional. ';
        html += 'Es posible que sea una notificación antigua o que los datos relacionados ya no existan en el sistema.';
        html += '</div>';
        console.warn('⚠️ No hay detalles para mostrar');
    }
    
    document.getElementById('modalContenido').innerHTML = html;
    
    // Configurar botón de navegación inteligente
    const btnIr = document.getElementById('irAUrl');
    let urlDestino = null;
    
    // Determinar la URL según el tipo de notificación (dentro del panel)
    const tipoUsuario = '<?= $tipo_usuario ?>';
    
    if (detalles && Object.keys(detalles).length > 0) {
        if (detalles.id_prestamo) {
            // Si es préstamo, ir al historial/calendario de préstamos dentro del panel
            if (tipoUsuario === 'Profesor') {
                urlDestino = 'Profesor.php?view=historial';
            } else if (tipoUsuario === 'Administrador') {
                urlDestino = 'Admin.php?view=historial_global';
            } else if (tipoUsuario === 'Encargado') {
                urlDestino = 'Encargado.php?view=historial'; // Redirigir al historial/calendario del encargado
            }
        } else if (detalles.id_reserva) {
            // Si es reserva, ir al historial/calendario dentro del panel
            if (tipoUsuario === 'Profesor') {
                urlDestino = 'Profesor.php?view=historial';
            } else if (tipoUsuario === 'Administrador') {
                urlDestino = 'Admin.php?view=historial_global';
            } else if (tipoUsuario === 'Encargado') {
                urlDestino = 'Encargado.php?view=historial'; // Redirigir al historial/calendario del encargado
            }
        }
    }
    
    // Si no se determinó una URL específica, usar la URL original de la notificación
    if (!urlDestino && url && url !== '#') {
        urlDestino = url;
    }
    
    if (urlDestino && !esCancelacion) {
        btnIr.style.display = 'block';
        btnIr.onclick = function() {
            // Marcar como leída
            if (notifId) {
                fetch('../../app/api/notificaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=marcar&id=' + notifId,
                    keepalive: true
                });
            }
            modalDetalles.hide();
            window.location.href = urlDestino;
        };
    } else {
        btnIr.style.display = 'none';
    }
    
    safeShowModal();
}

// Manejar click en la notificación completa (sin botón de detalles)
document.querySelectorAll('.notif-item').forEach(function(item){
    item.addEventListener('click', function(e){
        // Si hizo click en el botón de detalles, no hacer nada
        if (e.target.closest('.ver-detalles')) {
            return;
        }
        
        e.preventDefault();
        
        const id = this.dataset.notifId;
        const url = this.dataset.notifUrl;
        const titulo = this.dataset.notifTitulo;
        const detalles = JSON.parse(this.dataset.notifDetalles || '{}');
        const tipoUsuario = '<?= $tipo_usuario ?>';
        
        console.log('📧 Click en notificación:', {id: id, url: url});
        
        // Si tiene detalles, mostrar modal
        if (detalles && Object.keys(detalles).length > 0) {
            mostrarDetalles(titulo, detalles, url, id);
        } else {
            // Si no tiene detalles, marcar como leída
            if (id) {
                const currentItem = this;
                fetch('../../app/api/notificaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=marcar&id=' + id,
                    keepalive: true
                }).then(function() {
                    // Para Encargado, solo actualizar visualmente sin redirigir
                    if (tipoUsuario === 'Encargado') {
                        currentItem.classList.remove('list-group-item-primary');
                        currentItem.classList.add('list-group-item-light');
                        const badge = currentItem.querySelector('.badge');
                        if (badge) badge.remove();
                    } else {
                        // Para otros roles, redirigir si hay URL
                        if (url && url !== '#') {
                            window.location.href = url;
                        }
                    }
                });
            }
        }
    });
});

// Manejar click en el botón "Ver más detalles" (debe estar después del evento del contenedor)
document.querySelectorAll('.ver-detalles').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Evitar que se ejecute el click del contenedor
        
        const item = this.closest('.notif-item');
        if (!item) return;
        
        const titulo = item.dataset.notifTitulo;
        const url = item.dataset.notifUrl;
        const notifId = item.dataset.notifId;
        
        console.log('🔍 Click en Ver más detalles - Dataset completo:', item.dataset);
        
        let detalles = {};
        try {
            detalles = JSON.parse(item.dataset.notifDetalles || '{}');
            console.log('📋 Detalles parseados:', detalles);
        } catch (error) {
            console.error('❌ Error al parsear detalles:', error);
        }
        
        mostrarDetalles(titulo, detalles, url, notifId);
    });
});

}); // Cierre de DOMContentLoaded
</script>

<?php if (!defined('EMBEDDED_VIEW')): ?>
</body>
</html>
<?php endif; ?>
