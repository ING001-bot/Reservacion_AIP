<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

require "../config/conexion.php";
require '../controllers/ReservaController.php';
require_once '../controllers/PrestamoController.php';
require_once '../middleware/VerifyMiddleware.php';
require_once '../middleware/RouteGuard.php';
RouteGuard::enforceInternalNav();
require_once '../lib/VerificationService.php';
require_once '../models/UsuarioModel.php';

// Verificar si ya está verificado para reservas
$necesitaVerificacion = !isset($_SESSION['verified_reserva']) || $_SESSION['verified_reserva'] !== true;

// Solo enviar SMS si no hay código activo en sesión (optimización de velocidad)
if ($necesitaVerificacion && !isset($_POST['verificar_codigo']) && !isset($_GET['reenviar'])) {
    // Verificar si ya se envió un código recientemente (últimos 10 minutos)
    $codigoReciente = isset($_SESSION['otp_sent_reserva']) && 
                      isset($_SESSION['otp_sent_time_reserva']) && 
                      (time() - $_SESSION['otp_sent_time_reserva']) < 600; // 10 minutos
    
    if (!$codigoReciente) {
        $usuarioModel = new UsuarioModel($conexion);
        $usuario = $usuarioModel->obtenerPorId($_SESSION['id_usuario']);
        
        if ($usuario && !empty($usuario['telefono'])) {
            $verificationService = new \App\Lib\VerificationService($conexion);
            $resultadoSMS = $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'reserva');
            if (!empty($resultadoSMS['success'])) {
                $_SESSION['otp_sent_reserva'] = true;
                $_SESSION['otp_sent_time_reserva'] = time();
            } else {
                $errorVerificacion = '⚠️ No se pudo enviar el SMS de verificación. Verifica que tu número esté en formato +51XXXXXXXXX y vuelve a intentar.';
                if (!empty($resultadoSMS['error'])) {
                    $errorVerificacion .= ' Detalle: ' . htmlspecialchars($resultadoSMS['error']);
                }
            }
        } else {
            $errorVerificacion = '⚠️ No tienes un teléfono registrado. Actualiza tu número en tu perfil o solicita al administrador que lo registre con formato +51XXXXXXXXX.';
        }
    }
}

// Reenviar código si se solicita
if (isset($_GET['reenviar']) && $necesitaVerificacion) {
    $usuarioModel = new UsuarioModel($conexion);
    $usuario = $usuarioModel->obtenerPorId($_SESSION['id_usuario']);
    
    if ($usuario && !empty($usuario['telefono'])) {
        $verificationService = new \App\Lib\VerificationService($conexion);
        $resultadoSMS = $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'reserva');
        if (empty($resultadoSMS['success'])) {
            $errorVerificacion = '⚠️ No se pudo enviar el SMS de verificación. Verifica que tu número esté en formato +51XXXXXXXXX e inténtalo de nuevo.';
        }
        header('Location: Reserva.php');
        exit;
    }
}

// Procesar verificación de código
if (isset($_POST['verificar_codigo'])) {
    $codigo = $_POST['codigo_verificacion'] ?? '';
    $verificationService = new \App\Lib\VerificationService($conexion);
    
    if ($verificationService->verifyCode($_SESSION['id_usuario'], $codigo, 'reserva')) {
        $_SESSION['verified_reserva'] = true;
        // Ventana de validez de 10 minutos para controladores
        $_SESSION['otp_verified_until'] = time() + 10*60;
        // Flag de sesión (válido hasta cerrar sesión)
        $_SESSION['otp_verified'] = true;
        $necesitaVerificacion = false;
        $mensajeVerificacion = '✅ Código verificado correctamente. Ahora puedes realizar reservas.';
    } else {
        $errorVerificacion = '❌ Código incorrecto o expirado. Intenta nuevamente.';
    }
}

// Verificar SMS antes de permitir acciones de reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && !$necesitaVerificacion) {
    // Permitir la acción solo si ya está verificado
}

$nombreProfesor = $_SESSION['usuario'] ?? 'Invitado';
$controller = new ReservaController($conexion);
$prestamoController = new PrestamoController($conexion);

// SOLO obtener aulas de tipo AIP
$aulas = $controller->obtenerAulas('AIP');

// Obtener devoluciones del usuario actual (últimos 30 días, con fallback)
date_default_timezone_set('America/Lima');
$desde = date('Y-m-d', strtotime('-30 days'));
$hasta = date('Y-m-d');
$devoluciones = $prestamoController->obtenerPrestamosFiltrados('Devuelto', $desde, $hasta, '');

// Si no hay devoluciones en los últimos 30 días, obtener todas (sin límite de fecha)
if (empty($devoluciones)) {
    $devoluciones = $prestamoController->obtenerTodasLasDevoluciones();
}

// Filtrar para mostrar solo devoluciones del usuario actual (si es encargado, mostrar todas)
if ($_SESSION['tipo'] !== 'Administrador' && $_SESSION['tipo'] !== 'Encargado') {
    $devoluciones = array_filter($devoluciones, function($d) {
        return (int)($d['id_usuario'] ?? 0) === (int)$_SESSION['id_usuario'];
    });
}

// Verificar si hay aulas disponibles
if (empty($aulas)) {
    $errorAulas = '⚠️ No hay aulas de tipo AIP disponibles en este momento. Contacta al administrador.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'guardar') {
        $id_usuario = $_SESSION['id_usuario'];
        $id_aula = $_POST['id_aula'] ?? null;
        $fecha = $_POST['fecha'] ?? null;
        $hora_inicio = $_POST['hora_inicio'] ?? null;
        $hora_fin = $_POST['hora_fin'] ?? null;
        $controller->reservarAula($id_aula, $fecha, $hora_inicio, $hora_fin, $id_usuario);
    } elseif ($_POST['accion'] === 'eliminar') {
        $id_reserva = $_POST['id_reserva'] ?? null;
        $id_usuario = $_SESSION['id_usuario'];
        $controller->eliminarReserva($id_reserva, $id_usuario);
    }
} else {
    $controller->mensaje = "";
}

date_default_timezone_set('America/Lima');
$hoy = new DateTime('today');
$mañana = (clone $hoy)->modify('+1 day');
$fecha_min = $mañana->format('Y-m-d');

$fecha_default = $_POST['fecha'] ?? $fecha_min;
$id_aula_selected = $_POST['id_aula'] ?? (isset($aulas[0]['id_aula']) ? $aulas[0]['id_aula'] : null);
// Si venimos de una cancelación, usar aula/fecha canceladas para refrescar disponibilidad
if (!empty($_SESSION['flash_cancel'])) {
    $fc = $_SESSION['flash_cancel'];
    if (!empty($fc['fecha'])) { $fecha_default = $fc['fecha']; }
    if (!empty($fc['id_aula'])) { $id_aula_selected = $fc['id_aula']; }
    unset($_SESSION['flash_cancel']);
}

$reservas_existentes = [];
if (!empty($fecha_default) && !empty($id_aula_selected)) {
    $reservas_existentes = $controller->obtenerReservasPorFecha($id_aula_selected, $fecha_default);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Aula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <style>#cuadro-horas .btn { min-width: 110px; position: relative; overflow: hidden; }</style>
    <style>
        .verification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1100; /* Debajo de la navbar (1105) para no bloquear clics */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .code-input-verify {
            letter-spacing: 12px;
            font-size: 2rem;
            text-align: center;
            height: 70px;
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            font-weight: bold;
            margin: 20px 0;
        }
        .code-input-verify:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .error-shake {
            animation: shake 0.5s;
            border-color: #dc3545 !important;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .content-blocked {
            filter: blur(5px);
            pointer-events: none;
            user-select: none;
        }
        /* Medio botón ocupado (izquierda roja) */
        .btn-half-danger::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 50%;
            height: 100%;
            background: rgba(220,53,69,0.85); /* red */
            pointer-events: none;
        }
        /* Medio botón seleccionado (derecha azul para indicar que el fin también está incluido) */
        .btn-half-primary::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 50%;
            height: 100%;
            background: rgba(13,110,253,0.6); /* primary */
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-light">

<?php if ($necesitaVerificacion): ?>
<!-- Modal de Verificación -->
<div class="verification-overlay" id="verificationOverlay">
    <div class="verification-box">
        <div style="font-size: 4rem; color: #667eea; margin-bottom: 20px;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="mb-3">Verificación Requerida</h3>
        
        <?php if (isset($errorVerificacion)): ?>
            <div class="alert alert-danger"><?= $errorVerificacion ?></div>
        <?php endif; ?>
        
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle-fill me-2"></i>
            Hemos enviado un código de 6 dígitos a tu teléfono registrado
        </div>
        
        <p class="text-muted mb-3">Ingresa el código para acceder a las reservas</p>
        
        <form method="POST" id="formVerificacion">
            <input type="hidden" name="verificar_codigo" value="1">
            <input type="text" 
                   name="codigo_verificacion" 
                   id="codigoInput"
                   class="form-control code-input-verify" 
                   maxlength="6" 
                   pattern="\d{6}"
                   inputmode="numeric"
                   placeholder="000000"
                   autocomplete="off"
                   required
                   autofocus>
            
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                <i class="bi bi-check-circle me-2"></i>
                Verificar Código
            </button>
        </form>
        
        <div class="mt-3">
            <small class="text-muted">
                ¿No recibiste el código? 
                <a href="#" id="otp-reenviar" class="text-decoration-none">Reenviar</a>
            </small>
        </div>
    </div>
</div>

<script>
// Auto-submit cuando se completan 6 dígitos
document.getElementById('codigoInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length === 6) {
        document.getElementById('formVerificacion').submit();
    }
});

// Animación de error si existe
<?php if (isset($errorVerificacion)): ?>
document.getElementById('codigoInput').classList.add('error-shake');
setTimeout(() => {
    document.getElementById('codigoInput').classList.remove('error-shake');
    document.getElementById('codigoInput').select();
}, 500);
<?php endif; ?>

// Reenviar código con enfriamiento (sin recargar)
(function(){
  const link = document.getElementById('otp-reenviar');
  if (!link) return;
  let locked = false; let timer = null; let secs = 0; const orig = link.textContent;
  link.addEventListener('click', async function(ev){
    ev.preventDefault(); if (locked) return;
    locked = true; secs = 60; link.classList.add('disabled');
    link.textContent = `Reenviar (${secs}s)`;
    timer = setInterval(()=>{
      secs--; link.textContent = `Reenviar (${secs}s)`;
      if (secs <= 0){ clearInterval(timer); link.classList.remove('disabled'); link.textContent = orig; locked = false; }
    }, 1000);
    try { await fetch(window.location.pathname + '?reenviar=1', { credentials:'same-origin' }); } catch(e){}
  });
});
</script>
<?php endif; ?>

<div class="container py-4" <?= $necesitaVerificacion ? 'class="content-blocked"' : '' ?>>
    <h1 class="text-center text-brand mb-4">📅 Reservar Aula</h1>

    <!-- Aviso de anticipación -->
    <div class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem;"></i>
        <div>
            <strong>⚠️ Importante:</strong> Las reservas deben realizarse con al menos <strong>1 día de anticipación</strong>.
            No se permiten reservas para el mismo día.
        </div>
    </div>

    <?php if (isset($mensajeVerificacion)): ?>
        <div class="alert alert-success text-center shadow-sm">
            <?= htmlspecialchars($mensajeVerificacion) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($controller->mensaje)): ?>
        <div class="alert alert-<?= $controller->tipo ?> text-center shadow-sm">
            <?= htmlspecialchars($controller->mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card card-brand shadow-lg">
                <div class="card-body">
                    <form id="form-reserva" method="POST" class="row g-3" onsubmit="return false;">
                        <input type="hidden" name="accion" value="guardar">
                        <div class="col-12">
                            <label class="form-label">Profesor</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($nombreProfesor) ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Aula AIP Disponible</label>
                            <?php if (isset($errorAulas)): ?>
                                <div class="alert alert-warning"><?= $errorAulas ?></div>
                            <?php else: ?>
                                <select name="id_aula" class="form-select" required id="aula-select">
                                    <?php if (empty($aulas)): ?>
                                        <option value="">No hay aulas AIP disponibles</option>
                                    <?php else: ?>
                                        <?php foreach ($aulas as $aula): ?>
                                            <option value="<?= $aula['id_aula'] ?>" <?= ($id_aula_selected == $aula['id_aula']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($aula['nombre_aula']) ?> - Capacidad: <?= $aula['capacidad'] ?> personas (<?= strtoupper($aula['tipo']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Solo se muestran aulas de tipo AIP activas</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" required min="<?= $fecha_min ?>" value="<?= htmlspecialchars($fecha_default) ?>" id="fecha-select">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hora Inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hora Fin</label>
                            <input type="time" name="hora_fin" class="form-control" required>
                        </div>
                        <div class="col-12 text-center mt-2">
                            <button type="button" id="btn-reservar" class="btn btn-brand px-4">Reservar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Disponibilidad de Horas</label>
                        <span class="badge bg-primary-subtle text-primary-emphasis" id="fecha-badge">
                            <?= htmlspecialchars($fecha_default) ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="btn-group" role="group" aria-label="Turnos">
                            <button type="button" class="btn btn-outline-primary btn-sm active" id="btn-turno-manana">Turno Mañana</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-turno-tarde">Turno Tarde</button>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-limpiar-seleccion">Limpiar selección</button>
                    </div>
                    <div id="cuadro-horas" class="d-flex flex-wrap gap-2">
                        <?php
                        if (!empty($fecha_default) && !empty($id_aula_selected)) {
                            // Horario turno mañana: 06:00 - 12:45 (incluye hora de culminación)
                            $starts = ['06:00','06:45'];
                            $s = strtotime('07:10');
                            $pre_recreo_fin = strtotime('10:10');
                            while ($s < $pre_recreo_fin) { $starts[] = date('H:i', $s); $s += 45*60; }
                            $starts[] = '10:10';
                            $s = strtotime('10:30');
                            $endStart = strtotime('12:45'); // Incluir 12:45
                            while ($s <= $endStart) { $starts[] = date('H:i', $s); $s += 45*60; }

                            foreach ($starts as $inicio_hm) {
                                $inicio = $inicio_hm . ':00';
                                $fin_ts = strtotime($inicio_hm) + 45*60;
                                $fin_hm = date('H:i', $fin_ts);
                                $fin = $fin_hm . ':00';
                                $isRecreoMarker = ($inicio_hm === '10:10');
                                $ocupada = false;
                                if (!$isRecreoMarker) {
                                    foreach ($reservas_existentes as $res) {
                                        // REGLA: Un bloque queda ocupado si y solo si
                                        // el INICIO del bloque está dentro del rango de la reserva
                                        // [res_inicio, res_fin). Así evitamos marcar 06:45 cuando la
                                        // reserva empieza 07:10.
                                        $res_inicio = strlen($res['hora_inicio']) === 5 ? ($res['hora_inicio'] . ':00') : $res['hora_inicio'];
                                        $res_fin = strlen($res['hora_fin']) === 5 ? ($res['hora_fin'] . ':00') : $res['hora_fin'];

                                        $bloqueInicioDentro = ($inicio >= $res_inicio && $inicio < $res_fin);
                                        if ($bloqueInicioDentro) {
                                            $ocupada = true;
                                            break;
                                        }
                                    }
                                }
                                if ($isRecreoMarker) {
                                    echo "<button type='button' class='btn btn-success btn-sm mb-1' data-time='{$inicio_hm}' disabled title='Recreo'>{$inicio_hm}</button>";
                                    echo "<button type='button' class='btn btn-warning btn-sm mb-1' data-time='10:10-10:30' disabled title='Recreo'>10:10 - 10:30</button>";
                                } else {
                                    $clase = $ocupada ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm';
                                    echo "<button type='button' class='{$clase} mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
                                }
                            }
                        } else {
                            echo "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
                        }
                        ?>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-success">Disponible</span>
                        <span class="badge bg-danger ms-2">Ocupada</span>
                        <span class="badge bg-warning text-dark ms-2" title="10:10–10:30">Recreo (10:10–10:30)</span>
                    </div>
                    <div class="mt-2 small" id="texto-rango">
                        Hora de inicio: <strong id="txt-inicio">—</strong> · Hora de fin: <strong id="txt-fin">—</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center gap-2 my-3">
        <button id="btn-realizadas" class="btn btn-primary btn-sm" type="button">Reservas realizadas</button>
        <button id="btn-canceladas" class="btn btn-outline-primary btn-sm" type="button">Reservas canceladas</button>
        <button id="btn-devoluciones" class="btn btn-outline-primary btn-sm" type="button">Devoluciones</button>
    </div>

    <h2 class="text-center text-brand my-2">📖 Reservas Registradas</h2>
    <div id="tabla-realizadas" class="table-responsive shadow-lg">
        <table class="table table-hover align-middle text-center table-brand">
            <thead class="table-primary text-center">
            <tr>
                <th>Profesor</th>
                <th>Aula</th>
                <th>Capacidad</th>
                <th>Fecha</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php $reservas = $controller->obtenerReservas($_SESSION['id_usuario']); ?>
            <?php
            date_default_timezone_set('America/Lima');
            foreach ($reservas as $reserva):
                $fechaR = $reserva['fecha'];
                $horaIni = $reserva['hora_inicio'];
                if (strlen($horaIni) === 5) { $horaIni .= ':00'; }
                try {
                    $dtInicio = new DateTime($fechaR . ' ' . $horaIni, new DateTimeZone('America/Lima'));
                } catch (Exception $e) { $dtInicio = null; }
                $limite = $dtInicio ? (clone $dtInicio)->modify('-1 hour') : null;
                $ahora = new DateTime('now', new DateTimeZone('America/Lima'));
                $bloqueado = !$dtInicio || ($ahora > $limite);
            ?>
                <tr>
                    <td><?= htmlspecialchars($reserva['profesor']) ?></td>
                    <td><?= htmlspecialchars($reserva['nombre_aula']) ?></td>
                    <td><?= htmlspecialchars($reserva['capacidad']) ?></td>
                    <td><?= htmlspecialchars($reserva['fecha']) ?></td>
                    <td><?= htmlspecialchars($reserva['hora_inicio']) ?></td>
                    <td><?= htmlspecialchars($reserva['hora_fin']) ?></td>
                    <td>
                        <form method="POST" class="d-inline form-cancelar">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_reserva" value="<?= $reserva['id_reserva'] ?>">
                            <button type="button"
                                    class="btn btn-danger btn-sm cancelar-btn<?= $bloqueado ? ' disabled' : '' ?>"
                                    data-fecha="<?= htmlspecialchars($fechaR) ?>"
                                    data-hora-inicio="<?= htmlspecialchars($reserva['hora_inicio']) ?>"
                                    <?= $bloqueado ? 'disabled aria-disabled="true" title="La reserva ya ha pasado o está dentro de la hora límite"' : '' ?>>
                                Cancelar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php $canceladas = $controller->obtenerCanceladas($_SESSION['id_usuario']); ?>
    <div id="tabla-canceladas" class="table-responsive shadow-lg" style="display: none;">
        <table class="table table-hover align-middle text-center table-brand">
            <thead class="table-secondary text-center">
            <tr>
                <th>Estado</th>
                <th>Aula</th>
                <th>Fecha reservada</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Fecha cancelación</th>
                <th>Motivo</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($canceladas)): ?>
                <?php foreach ($canceladas as $rc): ?>
                    <tr>
                        <td><span class="badge bg-danger">CANCELADO</span></td>
                        <td><?= htmlspecialchars($rc['nombre_aula'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($rc['fecha'] ?? '') ?></td>
                        <td><?= htmlspecialchars($rc['hora_inicio'] ?? '') ?></td>
                        <td><?= htmlspecialchars($rc['hora_fin'] ?? '') ?></td>
                        <td><?= htmlspecialchars($rc['fecha_cancelacion'] ?? '') ?></td>
                        <td class="text-start" style="max-width: 420px; white-space: normal;">
                            <span class="badge bg-secondary me-1">Motivo</span>
                            <span class="text-muted"><?= nl2br(htmlspecialchars($rc['motivo'] ?? '')) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-muted">No tienes reservas canceladas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabla de Devoluciones -->
    <div id="tabla-devoluciones" class="table-responsive shadow-lg" style="display: none;">
        <table class="table table-hover align-middle text-center table-brand">
            <thead class="table-info text-center">
            <tr>
                <th>Equipo</th>
                <th>Tipo</th>
                <th>Responsable</th>
                <th>Aula</th>
                <th>Fecha Préstamo</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Fecha Devolución</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($devoluciones)): ?>
                <?php foreach ($devoluciones as $dev): ?>
                    <tr>
                        <td><?= htmlspecialchars($dev['nombre_equipo'] ?? '—') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($dev['tipo_equipo'] ?? '') ?></span></td>
                        <td><?= htmlspecialchars($dev['nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($dev['nombre_aula'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($dev['fecha_prestamo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($dev['hora_inicio'] ?? '') ?></td>
                        <td><?= htmlspecialchars($dev['hora_fin'] ?? '') ?></td>
                        <td><span class="badge bg-success"><?= htmlspecialchars($dev['fecha_devolucion'] ?? '—') ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-muted">No hay devoluciones registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script externo -->
<script src="../../Public/js/Reservas.js"></script>
<script src="../../Public/js/theme.js"></script>

<script>
// Alternar tablas realizadas/canceladas
document.addEventListener('DOMContentLoaded', function() {
    const btnRealizadas = document.getElementById('btn-realizadas');
    const btnCanceladas = document.getElementById('btn-canceladas');
    const btnDevoluciones = document.getElementById('btn-devoluciones');
    const tablaRealizadas = document.getElementById('tabla-realizadas');
    const tablaCanceladas = document.getElementById('tabla-canceladas');
    const tablaDevoluciones = document.getElementById('tabla-devoluciones');
    const formReserva = document.getElementById('form-reserva');
    const fechaInput = document.getElementById('fecha-select');
    let otpOk = false; // modal del servidor maneja verificación; no usar prompts duplicados

    function mostrarRealizadas() {
        tablaRealizadas.style.display = '';
        tablaCanceladas.style.display = 'none';
        tablaDevoluciones.style.display = 'none';
        btnRealizadas.classList.remove('btn-outline-primary');
        btnRealizadas.classList.add('btn-primary');
        btnCanceladas.classList.remove('btn-primary');
        btnCanceladas.classList.add('btn-outline-primary');
        btnDevoluciones.classList.remove('btn-primary');
        btnDevoluciones.classList.add('btn-outline-primary');
    }

    function mostrarCanceladas() {
        tablaRealizadas.style.display = 'none';
        tablaCanceladas.style.display = '';
        tablaDevoluciones.style.display = 'none';
        btnCanceladas.classList.remove('btn-outline-primary');
        btnCanceladas.classList.add('btn-primary');
        btnRealizadas.classList.remove('btn-primary');
        btnRealizadas.classList.add('btn-outline-primary');
        btnDevoluciones.classList.remove('btn-primary');
        btnDevoluciones.classList.add('btn-outline-primary');
    }

    function mostrarDevoluciones() {
        tablaRealizadas.style.display = 'none';
        tablaCanceladas.style.display = 'none';
        tablaDevoluciones.style.display = '';
        btnDevoluciones.classList.remove('btn-outline-primary');
        btnDevoluciones.classList.add('btn-primary');
        btnRealizadas.classList.remove('btn-primary');
        btnRealizadas.classList.add('btn-outline-primary');
        btnCanceladas.classList.remove('btn-primary');
        btnCanceladas.classList.add('btn-outline-primary');
    }

    btnRealizadas.addEventListener('click', mostrarRealizadas);
    btnCanceladas.addEventListener('click', mostrarCanceladas);
    btnDevoluciones.addEventListener('click', mostrarDevoluciones);

    // Si venimos de una cancelación exitosa, mostrar directamente la pestaña de canceladas
    <?php if (!empty($controller->mensaje) && $controller->tipo === 'success' && strpos($controller->mensaje, 'Reserva cancelada') !== false): ?>
        mostrarCanceladas();
        // Resaltar la última cancelación (primera fila)
        const firstRow = tablaCanceladas.querySelector('tbody tr');
        if (firstRow) {
            firstRow.classList.add('table-warning');
            setTimeout(() => firstRow.classList.remove('table-warning'), 3000);
        }
    <?php endif; ?>

    // Flujo OTP duplicado eliminado; se mantiene solo el modal del servidor

    // La confirmación y el envío se gestionan en Public/js/Reservas.js (SweetAlert)
});
</script>
</body>
</html>
