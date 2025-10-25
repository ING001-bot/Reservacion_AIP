<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y tiene id_usuario
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php"); 
    exit();
}

require_once '../controllers/PrestamoController.php';
require_once '../controllers/AulaController.php';
require_once '../middleware/VerifyMiddleware.php';
require_once '../lib/VerificationService.php';
require_once '../models/UsuarioModel.php';

// Verificar si ya está verificado para préstamos
$necesitaVerificacion = !isset($_SESSION['verified_prestamo']) || $_SESSION['verified_prestamo'] !== true;

// Si necesita verificación y no es una petición de verificación, enviar código
if ($necesitaVerificacion && !isset($_POST['verificar_codigo']) && !isset($_GET['reenviar'])) {
    $usuarioModel = new UsuarioModel($conexion);
    $usuario = $usuarioModel->obtenerPorId($_SESSION['id_usuario']);
    
    if ($usuario && !empty($usuario['telefono'])) {
        $verificationService = new \App\Lib\VerificationService($conexion);
        $resultadoSMS = $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'prestamo');
        if (empty($resultadoSMS['success'])) {
            $errorVerificacion = '⚠️ No se pudo enviar el SMS de verificación. Verifica que tu número esté en formato +51XXXXXXXXX y vuelve a intentar. ';
            if (!empty($resultadoSMS['error'])) {
                $errorVerificacion .= ' Detalle: ' . htmlspecialchars($resultadoSMS['error']);
            }
        }
    } else {
        $errorVerificacion = '⚠️ No tienes un teléfono registrado. Actualiza tu número en tu perfil o solicita al administrador que lo registre con formato +51XXXXXXXXX.';
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

// Cargar inventario por tipo con stock disponible (activos y disponibles para la fecha)
$fecha_prestamo_check = $_POST['fecha_prestamo'] ?? date('Y-m-d', strtotime('+1 day'));
$laptops = $prestamoController->listarEquiposPorTipoConStock('LAPTOP', $fecha_prestamo_check);
$proyectores = $prestamoController->listarEquiposPorTipoConStock('PROYECTOR', $fecha_prestamo_check);
$mouses = $prestamoController->listarEquiposPorTipoConStock('MOUSE', $fecha_prestamo_check);
$extensiones = $prestamoController->listarEquiposPorTipoConStock('EXTENSION', $fecha_prestamo_check);
$parlantes = $prestamoController->listarEquiposPorTipoConStock('PARLANTE', $fecha_prestamo_check);

// Calcular totales disponibles
$total_laptops = array_sum(array_column($laptops, 'disponible'));
$total_proyectores = array_sum(array_column($proyectores, 'disponible'));
$total_mouses = array_sum(array_column($mouses, 'disponible'));
$total_extensiones = array_sum(array_column($extensiones, 'disponible'));
$total_parlantes = array_sum(array_column($parlantes, 'disponible'));

// Procesar formulario (selección por equipo específico) – evitar cuando es POST de verificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verificar_codigo'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d');
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    $id_aula = $_POST['id_aula'] ?? null;

    // IDs seleccionados
    $id_laptop = (int)($_POST['id_laptop'] ?? 0);
    $id_proyector = (int)($_POST['id_proyector'] ?? 0);
    $use_mouse = isset($_POST['use_mouse']);
    $id_mouse = $use_mouse ? (int)($_POST['id_mouse'] ?? 0) : 0;
    $use_extension = isset($_POST['use_extension']);
    $id_extension = $use_extension ? (int)($_POST['id_extension'] ?? 0) : 0;
    $use_parlante = isset($_POST['use_parlante']);
    $id_parlante = $use_parlante ? (int)($_POST['id_parlante'] ?? 0) : 0;

    $equipos = array_values(array_filter([$id_laptop, $id_proyector, $id_mouse, $id_extension, $id_parlante]));

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
                $id_aula,
                $hora_fin ?: null
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
            z-index: 9999;
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
                <a href="?reenviar=1" class="text-decoration-none">Reenviar</a>
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
    <?php $noEquipos = empty($laptops) && empty($proyectores) && empty($mouses) && empty($extensiones) && empty($parlantes); ?>
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

    <?php if ($noEquipos): ?>
        <?php if (in_array($rol, ['Administrador','Encargado'], true)): ?>
            <div class="alert alert-warning">
                <strong>⚠️ No hay equipos disponibles.</strong>
                <p class="mb-0">Verifica que:</p>
                <ul class="mb-0">
                    <li>Se hayan registrado equipos en el sistema</li>
                    <li>Los tipos de equipos sean: <strong>LAPTOP, PROYECTOR, MOUSE, EXTENSION, PARLANTE</strong> (en mayúsculas)</li>
                    <li>Los equipos estén marcados como <strong>activos</strong></li>
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
            
            <!-- Indicadores de stock disponible -->
            <div class="alert alert-info mb-3">
                <strong>📊 Stock Disponible:</strong>
                <span class="badge bg-primary ms-2">💻 Laptops: <?= $total_laptops ?></span>
                <span class="badge bg-primary ms-2">📽 Proyectores: <?= $total_proyectores ?></span>
                <span class="badge bg-primary ms-2">🔌 Extensions: <?= $total_extensiones ?></span>
                <span class="badge bg-secondary ms-2">🖱 Mouses: <?= $total_mouses ?></span>
                <span class="badge bg-secondary ms-2">🔊 Parlantes: <?= $total_parlantes ?></span>
            </div>
            
            <div class="d-flex flex-wrap gap-2 mb-3 filters-actions">
                <?php $hasLap = $total_laptops>0; $hasProy = $total_proyectores>0; $hasExt = $total_extensiones>0; $hasParl = $total_parlantes>0; ?>
                <button type="button" class="btn btn-brand btn-control" id="pack-completo" <?= ($hasLap && $hasProy && $hasExt)?'':'disabled' ?>>📦 Laptop + Proyector + Extension</button>
                <button type="button" class="btn btn-outline-brand btn-control" id="pack-proyector" <?= ($hasProy && $hasExt)?'':'disabled' ?>>📽 Solo Proyector + Extension</button>
                <button type="button" class="btn btn-outline-brand btn-control" id="pack-laptop" <?= $hasLap?'':'disabled' ?>>💻 Solo Laptop</button>
                <button type="button" class="btn btn-outline-secondary btn-control" id="pack-parlante" <?= $hasParl?'':'disabled' ?>>🔊 Solo Parlante</button>
                <button type="button" class="btn btn-outline-danger btn-control" id="pack-limpiar">✖ Limpiar</button>
            </div>
            <form id="form-prestamo" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
                    <input type="date" name="fecha_prestamo" id="fecha_prestamo" 
                           class="form-control" required min="<?= $fecha_min ?>" 
                           value="<?= $fecha_default ?>">
                </div>

                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                        <h5 class="m-0">Selecciona equipos base</h5>
                        <div id="selection-summary" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Laptop <small class="text-muted">(<?= count($laptops) ?> disp.)</small></label>
                    <select class="form-select" name="id_laptop" id="id_laptop" <?= count($laptops)==0?'disabled':'' ?>>
                        <option value="0">Seleccionar...</option>
                        <?php foreach ($laptops as $eq): ?>
                            <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($laptops)==0): ?><div class="form-text text-danger">Sin stock disponible de Laptops</div><?php endif; ?>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Proyector <small class="text-muted">(<?= count($proyectores) ?> disp.)</small></label>
                    <select class="form-select" name="id_proyector" id="id_proyector" <?= count($proyectores)==0?'disabled':'' ?>>
                        <option value="0">Seleccionar...</option>
                        <?php foreach ($proyectores as $eq): ?>
                            <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($proyectores)==0): ?><div class="form-text text-danger">Sin stock disponible de Proyectores</div><?php endif; ?>
                </div>

                <div class="col-12">
                    <h5 class="mt-3">Complementos</h5>
                </div>
                <!-- Mouse (aparece cuando se elige una Laptop) -->
                <div class="col-md-4 col-12" id="wrap_mouse" style="display:none">
                    <label class="form-label">Mouse (opcional) <small class="text-muted">(<?= count($mouses) ?> disp.)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_mouse" name="use_mouse" value="1">
                        </span>
                        <select class="form-select" name="id_mouse" id="id_mouse" disabled <?= count($mouses)==0?'data-empty="1"':'' ?>>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($mouses as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (count($mouses)==0): ?><div class="form-text text-muted">No hay Mouse en stock.</div><?php endif; ?>
                </div>
                <!-- Extension (aparece cuando se elige un Proyector) -->
                <div class="col-md-4 col-12" id="wrap_extension" style="display:none">
                    <label class="form-label">Extension (opcional) <small class="text-muted">(<?= count($extensiones) ?> disp.)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_extension" name="use_extension" value="1">
                        </span>
                        <select class="form-select" name="id_extension" id="id_extension" disabled <?= count($extensiones)==0?'data-empty="1"':'' ?>>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($extensiones as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (count($extensiones)==0): ?><div class="form-text text-muted">No hay Extensions en stock.</div><?php endif; ?>
                </div>
                <!-- Parlante (aparece siempre como opcional) -->
                <div class="col-md-4 col-12" id="wrap_parlante">
                    <label class="form-label">Parlante (opcional) <small class="text-muted">(<?= count($parlantes) ?> disp.)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_parlante" name="use_parlante" value="1">
                        </span>
                        <select class="form-select" name="id_parlante" id="id_parlante" disabled <?= count($parlantes)==0?'data-empty="1"':'' ?>>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($parlantes as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (count($parlantes)==0): ?><div class="form-text text-muted">No hay Parlantes en stock.</div><?php endif; ?>
                </div>

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
                    <div class="text-uppercase small text-muted fw-semibold">Paso 3 · Confirmar</div>
                </div>
                <div class="col-12 text-center">
                    <?php $disableSubmit = empty($aulas) || ($noEquipos && !in_array($rol, ['Administrador','Encargado'], true)); ?>
                    <button type="submit" class="btn btn-brand px-4" <?= $disableSubmit ? 'disabled' : '' ?>>Enviar</button>
                    <?php if ($disableSubmit): ?>
                        <div class="text-muted mt-2">No es posible enviar mientras no haya aulas o equipos disponibles.</div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de préstamos -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="text-brand m-0">📖 Mis Préstamos Registrados</h2>
        <a href="../view/Dashboard.php" class="btn btn-outline-brand">🔙 Volver</a>
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
                const fechaSeleccionada = fechaInput.value;
                if (!fechaSeleccionada) return;
                
                // Validar que la fecha sea al menos 1 día después de hoy
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                const mañana = new Date(hoy);
                mañana.setDate(mañana.getDate() + 1);
                const fecha = new Date(fechaSeleccionada + 'T00:00:00');
                
                if (fecha < mañana) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: '⚠️ Fecha no permitida',
                        text: 'Solo puedes solicitar préstamos a partir del día siguiente. Los préstamos deben hacerse con anticipación, no el mismo día.',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
                // La verificación OTP se gestiona con el modal del servidor; no duplicar en frontend.
            });
        }
        // Flujo OTP duplicado eliminado; se mantiene solo el modal del servidor.
        const selLaptop = document.getElementById('id_laptop');
        const selProy = document.getElementById('id_proyector');
        const wrapMouse = document.getElementById('wrap_mouse');
        const wrapExt = document.getElementById('wrap_extension');
        const useMouse = document.getElementById('use_mouse');
        const useExt = document.getElementById('use_extension');
        const selMouse = document.getElementById('id_mouse');
        const selExt = document.getElementById('id_extension');
        const useParl = document.getElementById('use_parlante');
        const selParl = document.getElementById('id_parlante');
        // Botones de packs
        const btnPackCompleto = document.getElementById('pack-completo');
        const btnPackProy = document.getElementById('pack-proyector');
        const btnPackLap = document.getElementById('pack-laptop');
        const btnPackClear = document.getElementById('pack-limpiar');

        function refreshComplements(){
            const hasLaptop = parseInt(selLaptop.value||'0',10) > 0;
            wrapMouse.style.display = hasLaptop ? '' : 'none';
            if (!hasLaptop) { useMouse.checked = false; selMouse.disabled = true; selMouse.value = '0'; }
            const hasProy = parseInt(selProy.value||'0',10) > 0;
            wrapExt.style.display = hasProy ? '' : 'none';
            if (!hasProy) { useExt.checked = false; selExt.disabled = true; selExt.value = '0'; }
        }
        function toggleSelect(chk, sel){ sel.disabled = !chk.checked; if (!chk.checked) sel.value = '0'; }
        function firstAvailable(select){
            // Selecciona la primera opción válida distinta de 0
            if (!select) return;
            for (let i=0;i<select.options.length;i++){
                const opt = select.options[i];
                if (opt.value && opt.value !== '0'){ select.value = opt.value; break; }
            }
        }

        function updateSummary(){
            var box = document.getElementById('selection-summary');
            if (!box) return;
            box.innerHTML = '';
            function addChip(label){
                var span = document.createElement('span');
                span.className = 'badge bg-light text-dark border';
                span.textContent = label;
                box.appendChild(span);
            }
            // Base
            if (selLaptop && selLaptop.value !== '0'){
                addChip('💻 Laptop');
            }
            if (selProy && selProy.value !== '0'){
                addChip('📽 Proyector');
            }
            // Complementos
            if (useMouse && useMouse.checked && selMouse.value !== '0'){
                addChip('🖱 Mouse');
            }
            if (useExt && useExt.checked && selExt.value !== '0'){
                addChip('🔌 Extensión');
            }
            if (useParl && useParl.checked && selParl.value !== '0'){
                addChip('🔊 Parlante');
            }
        }

        // Acciones rápidas
        if (btnPackCompleto){ btnPackCompleto.addEventListener('click', ()=>{
            firstAvailable(selLaptop);
            firstAvailable(selProy);
            refreshComplements();
            useExt.checked = true; toggleSelect(useExt, selExt); firstAvailable(selExt);
            // Reset parlante opcional
            if (useParl){ useParl.checked = false; toggleSelect(useParl, selParl); }
            updateSummary();
        }); }
        if (btnPackProy){ btnPackProy.addEventListener('click', ()=>{
            selLaptop.value = '0';
            firstAvailable(selProy);
            refreshComplements();
            useExt.checked = true; toggleSelect(useExt, selExt); firstAvailable(selExt);
            if (useMouse){ useMouse.checked = false; toggleSelect(useMouse, selMouse); }
            if (useParl){ useParl.checked = false; toggleSelect(useParl, selParl); }
            updateSummary();
        }); }
        if (btnPackLap){ btnPackLap.addEventListener('click', ()=>{
            firstAvailable(selLaptop);
            selProy.value = '0';
            refreshComplements();
            if (useExt){ useExt.checked = false; toggleSelect(useExt, selExt); }
            if (useParl){ useParl.checked = false; toggleSelect(useParl, selParl); }
            updateSummary();
        }); }
        if (btnPackClear){ btnPackClear.addEventListener('click', ()=>{
            selLaptop.value = '0'; selProy.value = '0';
            if (useMouse){ useMouse.checked = false; }
            if (useExt){ useExt.checked = false; }
            if (useParl){ useParl.checked = false; }
            toggleSelect(useMouse, selMouse);
            toggleSelect(useExt, selExt);
            toggleSelect(useParl, selParl);
            refreshComplements();
            updateSummary();
        }); }
        // Solo Parlante
        const btnPackParl = document.getElementById('pack-parlante');
        if (btnPackParl){ btnPackParl.addEventListener('click', ()=>{
            selLaptop.value = '0'; selProy.value = '0';
            refreshComplements();
            if (useMouse){ useMouse.checked = false; toggleSelect(useMouse, selMouse); }
            if (useExt){ useExt.checked = false; toggleSelect(useExt, selExt); }
            if (useParl){
                useParl.checked = true; toggleSelect(useParl, selParl); firstAvailable(selParl);
            }
            updateSummary();
        }); }

        selLaptop.addEventListener('change', refreshComplements);
        selProy.addEventListener('change', refreshComplements);
        if (useMouse) useMouse.addEventListener('change', ()=>{ toggleSelect(useMouse, selMouse); updateSummary(); });
        if (useExt) useExt.addEventListener('change', ()=>{ toggleSelect(useExt, selExt); updateSummary(); });
        if (useParl) useParl.addEventListener('change', ()=>{ toggleSelect(useParl, selParl); updateSummary(); });
        // Actualizar resumen ante cambios de selects
        ['id_laptop','id_proyector','id_mouse','id_extension','id_parlante'].forEach(function(id){
            var el = document.getElementById(id);
            if (el){ el.addEventListener('change', updateSummary); }
        });
        refreshComplements();
        updateSummary();
    })();
</script>
</body>
</html>
