<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y tiene id_usuario
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php"); 
    exit();
}

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

require_once '../controllers/PrestamoController.php';
require_once '../controllers/AulaController.php';
require_once '../middleware/VerifyMiddleware.php';
require_once '../lib/VerificationService.php';
require_once '../models/UsuarioModel.php';

// Verificar si ya está verificado para préstamos
$necesitaVerificacion = !isset($_SESSION['verified_prestamo']) || $_SESSION['verified_prestamo'] !== true;

// Solo enviar SMS si no hay código activo en sesión (optimización de velocidad)
if ($necesitaVerificacion && !isset($_POST['verificar_codigo']) && !isset($_GET['reenviar'])) {
    // Verificar si ya se envió un código recientemente (últimos 10 minutos)
    $codigoReciente = isset($_SESSION['otp_sent_prestamo']) && 
                      isset($_SESSION['otp_sent_time_prestamo']) && 
                      (time() - $_SESSION['otp_sent_time_prestamo']) < 600; // 10 minutos
    
    if (!$codigoReciente) {
        $usuarioModel = new UsuarioModel($conexion);
        $usuario = $usuarioModel->obtenerPorId($_SESSION['id_usuario']);
        
        if ($usuario && !empty($usuario['telefono'])) {
            $verificationService = new \App\Lib\VerificationService($conexion);
            $resultadoSMS = $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'prestamo');
            if (!empty($resultadoSMS['success'])) {
                $_SESSION['otp_sent_prestamo'] = true;
                $_SESSION['otp_sent_time_prestamo'] = time();
            } else {
                $errorVerificacion = '⚠️ No se pudo enviar el SMS de verificación. Verifica que tu número esté en formato +51XXXXXXXXX y vuelve a intentar. ';
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
        $resultadoSMS = $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'prestamo');
        if (empty($resultadoSMS['success'])) {
            $errorVerificacion = '⚠️ No se pudo enviar el SMS de verificación. Verifica que tu número esté en formato +51XXXXXXXXX e inténtalo de nuevo.';
        }
        header('Location: Prestamo.php');
        exit;
    }
}

// Procesar verificación de código
if (isset($_POST['verificar_codigo'])) {
    $codigo = $_POST['codigo_verificacion'] ?? '';
    $verificationService = new \App\Lib\VerificationService($conexion);
    
    if ($verificationService->verifyCode($_SESSION['id_usuario'], $codigo, 'prestamo')) {
        $_SESSION['verified_prestamo'] = true;
        // Ventana de validez de 10 minutos para controladores
        $_SESSION['otp_verified_until'] = time() + 10*60;
        // Flag de sesión válido hasta cerrar sesión
        $_SESSION['otp_verified'] = true;
        $necesitaVerificacion = false;
        $mensajeVerificacion = '✅ Código verificado correctamente. Ahora puedes solicitar préstamos.';
    } else {
        $errorVerificacion = '❌ Código incorrecto o expirado. Intenta nuevamente.';
    }
}

$prestamoController = new PrestamoController($conexion);
$aulaController = new AulaController($conexion);

$mensaje = '';
$mensaje_tipo = '';
$rol = $_SESSION['tipo'] ?? 'Profesor';

// Solo aulas de tipo REGULAR para préstamos (no AIP)
$aulas = $aulaController->listarAulas('REGULAR');

// SISTEMA DINÁMICO: Obtiene TODOS los tipos de equipos registrados en BD
$fecha_prestamo_check = $_POST['fecha_prestamo'] ?? date('Y-m-d', strtotime('+1 day'));
$tipos_equipos = $prestamoController->listarTodosLosTiposConStock($fecha_prestamo_check);

// Calcular total de equipos disponibles en general
$total_equipos_disponibles = 0;
foreach ($tipos_equipos as $tipo => $data) {
    $total_equipos_disponibles += $data['total_disponible'] ?? 0;
}

// Procesar formulario - Recolectar TODOS los IDs de equipos seleccionados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verificar_codigo'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d');
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    $id_aula = $_POST['id_aula'] ?? null;

    // Recolectar dinámicamente todos los equipos seleccionados
    $equipos = [];
    foreach ($_POST as $key => $value) {
        // Buscar campos que empiecen con "equipo_"
        if (strpos($key, 'equipo_') === 0 && !empty($value) && (int)$value > 0) {
            $equipos[] = (int)$value;
        }
    }

    if (!$hora_inicio) {
        $mensaje = '⚠ Debes ingresar la hora de inicio.';
        $mensaje_tipo = 'danger';
    } elseif (!$id_aula || $id_aula === '') {
        $mensaje = '⚠ Debes seleccionar un aula.';
        $mensaje_tipo = 'danger';
    } elseif (empty($equipos)) {
        $mensaje = '⚠ Debes seleccionar al menos un equipo.';
        $mensaje_tipo = 'danger';
    } else {
        // Validar que el aula existe
        $id_aula = (int)$id_aula;
        if ($id_aula <= 0) {
            $mensaje = '⚠ ID de aula inválido.';
            $mensaje_tipo = 'danger';
        } else {
            $resultado = $prestamoController->guardarPrestamosMultiple(
                (int)$id_usuario,
                $equipos,
                $fecha_prestamo,
                $hora_inicio,
                $hora_fin ?: null,
                $id_aula
            );
            $mensaje = $resultado['mensaje'] ?? '';
            $mensaje_tipo = ($resultado['tipo'] ?? '') === 'error' ? 'danger' : 'success';
        }
    }
}

// Obtener préstamos del usuario (solo individuales)
$id_usuario = $_SESSION['id_usuario'];
$prestamosIndividuales = $prestamoController->listarPrestamosPorUsuario((int)$id_usuario);
$usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');

date_default_timezone_set('America/Lima');
$hoy = new DateTime('today');
$mañana = (clone $hoy)->modify('+1 day');
$fecha_min = $mañana->format('Y-m-d');
$fecha_default = $fecha_min;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Préstamo de Equipos - <?= $usuario ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../Public/css/brand.css">
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
    </style>
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

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
        
        <p class="text-muted mb-3">Ingresa el código para acceder a los préstamos</p>
        
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
})();
</script>
<?php endif; ?>

<div class="container py-4" <?= $necesitaVerificacion ? 'style="filter: blur(5px); pointer-events: none;"' : '' ?>>
    <h1 class="text-center text-brand mb-4">💻 Préstamo de Equipos</h1>

    <!-- Aviso de anticipación -->
    <div class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem;"></i>
        <div>
            <strong>⚠️ Importante:</strong> Los préstamos deben solicitarse con al menos <strong>1 día de anticipación</strong>.
            No se permiten préstamos para el mismo día.
        </div>
    </div>

    <?php if (isset($mensajeVerificacion)): ?>
        <div class="alert alert-success text-center shadow-sm">
            <?= htmlspecialchars($mensajeVerificacion) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $mensaje_tipo ?> text-center shadow-sm">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Verificar stock y aulas -->
    <?php if (empty($aulas)): ?>
        <?php if (in_array($rol, ['Administrador','Encargado'], true)): ?>
            <div class="alert alert-danger">
                <strong>❌ No hay aulas REGULAR disponibles.</strong>
                <p class="mb-0">Debes crear al menos un aula de tipo REGULAR para poder registrar préstamos.</p>
                <a href="Admin.php?view=aulas" class="btn btn-sm btn-primary mt-2">Ir a Gestión de Aulas</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠ No hay aulas REGULAR disponibles.</strong>
                <p class="mb-0">Por favor, contacta al Encargado o al Administrador para habilitar aulas REGULAR.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($tipos_equipos) || count($tipos_equipos) === 0): ?>
        <?php if (in_array($rol, ['Administrador','Encargado'], true)): ?>
            <div class="alert alert-warning">
                <strong>⚠️ No hay equipos disponibles.</strong>
                <p class="mb-0">Verifica que:</p>
                <ul class="mb-0">
                    <li>Se hayan registrado equipos en el sistema</li>
                    <li>Los equipos estén marcados como <strong>activos</strong></li>
                    <li>Los equipos tengan <strong>stock disponible</strong> para la fecha seleccionada</li>
                </ul>
                <a href="Admin.php?view=equipos" class="btn btn-sm btn-primary mt-2">Ir a Gestión de Equipos</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>ℹ️ No hay equipos disponibles por ahora.</strong>
                <p class="mb-0">Por favor, contacta al Encargado o al Administrador para consultar disponibilidad.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Formulario (Pack) -->
    <div class="card card-brand shadow-lg mb-4">
        <div class="card-body">
            <div class="mb-2 text-uppercase small text-muted fw-semibold">Paso 1 · Selección rápida</div>
            
            <!-- Indicadores de stock disponible DINÁMICOS -->
            <div class="alert alert-info mb-3">
                <strong>📊 Stock Disponible:</strong>
                <?php foreach ($tipos_equipos as $tipo => $data): ?>
                    <?php 
                    $emoji = '📦'; // Default
                    if (stripos($tipo, 'LAPTOP') !== false) $emoji = '💻';
                    elseif (stripos($tipo, 'PROYECTOR') !== false) $emoji = '📽';
                    elseif (stripos($tipo, 'EXTENSION') !== false || stripos($tipo, 'CABLE') !== false) $emoji = '🔌';
                    elseif (stripos($tipo, 'MOUSE') !== false) $emoji = '🖱';
                    elseif (stripos($tipo, 'PARLANTE') !== false || stripos($tipo, 'ALTAVOZ') !== false) $emoji = '🔊';
                    elseif (stripos($tipo, 'TABLET') !== false) $emoji = '📱';
                    elseif (stripos($tipo, 'TECLADO') !== false) $emoji = '⌨️';
                    ?>
                    <span class="badge bg-primary ms-2"><?= $emoji ?> <?= htmlspecialchars($tipo) ?>: <?= (int)$data['total_disponible'] ?></span>
                <?php endforeach; ?>
                <?php if (empty($tipos_equipos)): ?>
                    <span class="text-muted">No hay equipos disponibles</span>
                <?php endif; ?>
            </div>
            
            <div class="d-flex flex-wrap gap-2 mb-3 filters-actions">
                <?php 
                // Verificar qué tipos de equipos están disponibles
                $tiene_laptop = isset($tipos_equipos['LAPTOP']) && !empty($tipos_equipos['LAPTOP']['equipos']);
                $tiene_proyector = isset($tipos_equipos['PROYECTOR']) && !empty($tipos_equipos['PROYECTOR']['equipos']);
                $tiene_extension = isset($tipos_equipos['EXTENSION']) && !empty($tipos_equipos['EXTENSION']['equipos']);
                $tiene_parlante = isset($tipos_equipos['PARLANTE']) && !empty($tipos_equipos['PARLANTE']['equipos']);
                ?>
                
                <button type="button" class="btn btn-brand btn-control pack-btn" 
                        data-laptop="1" data-proyector="1" data-extension="1"
                        <?= ($tiene_laptop && $tiene_proyector && $tiene_extension) ? '' : 'disabled' ?>>
                    📦 Laptop + Proyector + Extension
                </button>
                
                <button type="button" class="btn btn-outline-brand btn-control pack-btn" 
                        data-proyector="1" data-extension="1"
                        <?= ($tiene_proyector && $tiene_extension) ? '' : 'disabled' ?>>
                    📽 Solo Proyector + Extension
                </button>
                
                <button type="button" class="btn btn-outline-brand btn-control pack-btn" 
                        data-laptop="1"
                        <?= $tiene_laptop ? '' : 'disabled' ?>>
                    💻 Solo Laptop
                </button>
                
                <button type="button" class="btn btn-outline-secondary btn-control pack-btn" 
                        data-parlante="1"
                        <?= $tiene_parlante ? '' : 'disabled' ?>>
                    🔊 Solo Parlante
                </button>
                
                <button type="button" class="btn btn-outline-danger btn-control" id="limpiar-seleccion">
                    ✖ Limpiar
                </button>
            </div>
            
            <form id="form-prestamo" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
                    <input type="date" name="fecha_prestamo" id="fecha_prestamo" 
                           class="form-control" required min="<?= $fecha_min ?>" 
                           value="<?= $fecha_default ?>">
                </div>

                <div class="col-12">
                    <h5 class="mt-2">Selecciona Equipos</h5>
                </div>
                
                <?php if (!empty($tipos_equipos)): ?>
                    <?php foreach ($tipos_equipos as $tipo => $data): ?>
                        <?php 
                        $equipos_del_tipo = $data['equipos'] ?? [];
                        $total_disponible = $data['total_disponible'] ?? 0;
                        if (empty($equipos_del_tipo)) continue;
                        
                        // Determinar emoji
                        $emoji = '📦';
                        if (stripos($tipo, 'LAPTOP') !== false) $emoji = '💻';
                        elseif (stripos($tipo, 'PROYECTOR') !== false) $emoji = '📽';
                        elseif (stripos($tipo, 'EXTENSION') !== false || stripos($tipo, 'CABLE') !== false) $emoji = '🔌';
                        elseif (stripos($tipo, 'MOUSE') !== false) $emoji = '🖱';
                        elseif (stripos($tipo, 'PARLANTE') !== false || stripos($tipo, 'ALTAVOZ') !== false) $emoji = '🔊';
                        elseif (stripos($tipo, 'TABLET') !== false) $emoji = '📱';
                        elseif (stripos($tipo, 'TECLADO') !== false) $emoji = '⌨️';
                        
                        $field_name = 'equipo_' . strtolower(str_replace(' ', '_', $tipo));
                        ?>
                        <div class="col-md-4 col-12">
                            <label class="form-label">
                                <?= $emoji ?> <?= htmlspecialchars(ucfirst(strtolower($tipo))) ?> 
                                <small class="text-muted">(<?= count($equipos_del_tipo) ?> disp.)</small>
                            </label>
                            <select class="form-select equipo-select" name="<?= $field_name ?>" data-tipo="<?= htmlspecialchars($tipo) ?>">
                                <option value="0">Seleccionar...</option>
                                <?php foreach ($equipos_del_tipo as $eq): ?>
                                    <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No hay tipos de equipos registrados o disponibles para la fecha seleccionada.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-12 mt-2">
                    <div class="text-uppercase small text-muted fw-semibold">Paso 2 · Aula y horario</div>
                </div>
                <div class="col-md-6">
                    <label for="id_aula" class="form-label">Aula</label>
                    <select name="id_aula" id="id_aula" class="form-select" required <?= empty($aulas)?'disabled':'' ?>>
                        <option value="">-- Selecciona un aula --</option>
                        <?php foreach ($aulas as $a): ?>
                            <option value="<?= (int)$a['id_aula'] ?>"><?= htmlspecialchars($a['nombre_aula']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($aulas)): ?>
                        <?php if (in_array($rol, ['Administrador','Encargado'], true)): ?>
                            <div class="form-text text-danger">No hay aulas REGULAR registradas. <a href="Admin.php?view=aulas" class="fw-bold">Crear aula REGULAR</a></div>
                        <?php else: ?>
                            <div class="form-text text-muted">No hay aulas REGULAR disponibles por ahora. Contacta al Encargado o Administrador.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="hora_inicio" class="form-label">Hora de inicio</label>
                    <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label for="hora_fin" class="form-label">Hora de fin</label>
                    <input type="time" name="hora_fin" id="hora_fin" class="form-control">
                </div>

                <div class="col-12 mt-2">
                    <div class="text-uppercase small text-muted fw-semibold">Paso 2 · Confirmar</div>
                </div>
                <div class="col-12 text-center">
                    <?php $disableSubmit = empty($aulas) || empty($tipos_equipos); ?>
                    <button type="submit" class="btn btn-brand px-4" <?= $disableSubmit ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-2"></i>Solicitar Préstamo
                    </button>
                    <?php if ($disableSubmit): ?>
                        <div class="text-muted mt-2">
                            <?php if (empty($aulas)): ?>
                                No hay aulas REGULAR disponibles.
                            <?php elseif (empty($tipos_equipos)): ?>
                                No hay equipos disponibles para la fecha seleccionada.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de préstamos -->
    <div class="mb-2">
        <h2 class="text-brand">📖 Mis Préstamos Registrados</h2>
    </div>
    <div class="table-responsive shadow-lg">
        <table class="table table-hover align-middle text-center table-brand">
            <thead class="table-primary text-center">
                <tr>
                    <th>Equipo(s)</th>
                    <th>Aula</th>
                    <th>Fecha</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                    <th>Estado</th>
                    <th>Devolución</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamosIndividuales)): ?>
                    <tr>
                        <td colspan="7" class="text-muted py-4">
                            No tienes préstamos registrados aún.
                        </td>
                    </tr>
                <?php endif; ?>
                
                <!-- Préstamos individuales (sistema antiguo) -->
                <?php 
                $prestamosAgrupados = [];
                foreach ($prestamosIndividuales as $p) {
                    $key = $p['fecha_prestamo'] . '|' . $p['hora_inicio'] . '|' . ($p['id_aula'] ?? 0);
                    if (!isset($prestamosAgrupados[$key])) {
                        $prestamosAgrupados[$key] = [
                            'equipos' => [],
                            'aula' => $p['nombre_aula'] ?? '-',
                            'fecha' => $p['fecha_prestamo'],
                            'hora_inicio' => $p['hora_inicio'],
                            'hora_fin' => $p['hora_fin'] ?? '-',
                            'estado' => $p['estado'],
                            'fecha_devolucion' => $p['fecha_devolucion'] ?? '-'
                        ];
                    }
                    $prestamosAgrupados[$key]['equipos'][] = strip_tags($p['nombre_equipo'] ?? 'Equipo');
                }
                
                foreach ($prestamosAgrupados as $grupo): ?>
                    <tr>
                        <td>
                            <?php 
                            // Ordenar por prioridad visible: Laptop, Proyector, Extension, Mouse, Parlante
                            $prioridad = function(string $nombre): int {
                                $n = mb_strtolower($nombre, 'UTF-8');
                                if (strpos($n, 'laptop') !== false) return 1;
                                if (strpos($n, 'proyector') !== false) return 2;
                                if (strpos($n, 'extension') !== false || strpos($n, 'extensión') !== false) return 3;
                                if (strpos($n, 'mouse') !== false) return 4;
                                if (strpos($n, 'parlante') !== false) return 5;
                                return 99;
                            };
                            $equipos = $grupo['equipos'];
                            usort($equipos, function($a, $b) use ($prioridad) {
                                $pa = $prioridad($a); $pb = $prioridad($b);
                                if ($pa === $pb) return strcasecmp($a, $b);
                                return $pa <=> $pb;
                            });
                            // Sanear y normalizar: quitar etiquetas HTML y 'Extension' -> 'Extension'
                            $equipos = array_map(function($e){
                                $trim = trim((string)strip_tags($e));
                                return preg_replace('/^(?i)extension$/u', 'Extension', $trim);
                            }, $equipos);
                            echo htmlspecialchars(implode(' · ', $equipos));
                            ?>
                        </td>
                        <td><?= htmlspecialchars($grupo['aula']) ?></td>
                        <td><?= htmlspecialchars($grupo['fecha']) ?></td>
                        <td><?= htmlspecialchars($grupo['hora_inicio']) ?></td>
                        <td><?= htmlspecialchars($grupo['hora_fin']) ?></td>
                        <td>
                            <?php if ($grupo['estado'] === 'Prestado'): ?>
                                <span class="badge bg-warning">Prestado</span>
                            <?php else: ?>
                                <span class="badge bg-success">Devuelto</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($grupo['fecha_devolucion']) ?></td>
                    </tr>
                <?php endforeach; ?>
                
            </tbody>
        </table>
    </div>
                    
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/theme.js"></script>
<script>
    (function(){
        // Validación de fecha antes de enviar el formulario
        const form = document.querySelector('form[method="POST"]');
        const fechaInput = document.getElementById('fecha_prestamo');
        
        let otpOk = false;
        if (form && fechaInput) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const fechaSeleccionada = fechaInput.value;
                if (!fechaSeleccionada) return;

                // Validar que la fecha sea al menos 1 día después de hoy
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                const mañana = new Date(hoy);
                mañana.setDate(mañana.getDate() + 1);
                const fecha = new Date(fechaSeleccionada + 'T00:00:00');

                if (fecha < mañana) {
                    Swal.fire({
                        icon: 'error',
                        title: '⚠️ Fecha no permitida',
                        text: 'Solo puedes solicitar préstamos a partir del día siguiente. Los préstamos deben hacerse con anticipación, no el mismo día.',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }

                // Confirmación bonita
                Swal.fire({
                    title: '¿Confirmar solicitud de préstamo?',
                    text: 'Se registrará tu solicitud con los equipos seleccionados.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, enviar',
                    cancelButtonText: 'Volver',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#1e6bd6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }
        // Flujo OTP duplicado eliminado; se mantiene solo el modal del servidor.
        // Sistema de Packs Rápidos (dinámico y compatible con nuevos tipos)
        const packButtons = document.querySelectorAll('.pack-btn');
        const limpiarBtn = document.getElementById('limpiar-seleccion');
        
        // Función para seleccionar el primer equipo disponible de un tipo
        function seleccionarPrimerEquipo(tipoEquipo) {
            const select = document.querySelector(`select[data-tipo="${tipoEquipo}"]`);
            if (!select) return false;
            
            // Buscar la primera opción que no sea "0"
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value !== '0') {
                    select.value = select.options[i].value;
                    return true;
                }
            }
            return false;
        }
        
        // Función para limpiar todos los equipos
        function limpiarTodos() {
            const selects = document.querySelectorAll('.equipo-select');
            selects.forEach(select => {
                select.value = '0';
            });
        }
        
        // Manejar clicks en los botones de packs
        packButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Primero limpiar todo
                limpiarTodos();
                
                // Activar equipos según el pack
                if (this.dataset.laptop) {
                    seleccionarPrimerEquipo('LAPTOP');
                }
                if (this.dataset.proyector) {
                    seleccionarPrimerEquipo('PROYECTOR');
                }
                if (this.dataset.extension) {
                    seleccionarPrimerEquipo('EXTENSION');
                }
                if (this.dataset.mouse) {
                    seleccionarPrimerEquipo('MOUSE');
                }
                if (this.dataset.parlante) {
                    seleccionarPrimerEquipo('PARLANTE');
                }
                
                // Resaltar botón activo
                packButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Botón limpiar
        if (limpiarBtn) {
            limpiarBtn.addEventListener('click', function() {
                limpiarTodos();
                packButtons.forEach(b => b.classList.remove('active'));
            });
        }
    })();
</script>
</body>
</html>
