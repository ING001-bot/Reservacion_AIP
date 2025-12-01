<?php
// Configuración - Vista para Profesor
if (!defined('EMBEDDED_VIEW')) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'Profesor') {
        header('Location: ../../Public/index.php');
        exit;
    }
}

require_once __DIR__ . '/../controllers/ConfiguracionController.php';

$id_usuario = $_SESSION['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0);
$configController = new ConfiguracionController();

$mensaje = '';
$mensaje_tipo = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar_datos'])) {
        $res = $configController->actualizarDatosPersonales(
            $id_usuario,
            $_POST['nombre'] ?? '',
            $_POST['correo'] ?? '',
            $_POST['telefono'] ?? null
        );
        // Actualizar también la biografía si viene en el formulario
        if (!$res['error'] && isset($_POST['bio'])) {
            $configController->actualizarBio($id_usuario, $_POST['bio'] ?? '');
        }
        $mensaje = $res['mensaje'];
        $mensaje_tipo = $res['error'] ? 'danger' : 'success';
    }

    if (isset($_POST['actualizar_bio'])) {
        $res = $configController->actualizarBio($id_usuario, $_POST['bio'] ?? '');
        $mensaje = $res['mensaje'];
        $mensaje_tipo = $res['error'] ? 'danger' : 'success';
    }

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $res = $configController->actualizarFoto($id_usuario, $_FILES['foto_perfil']);
        $mensaje = $res['mensaje'];
        $mensaje_tipo = $res['error'] ? 'danger' : 'success';
    }

    if (isset($_POST['eliminar_foto'])) {
        $res = $configController->eliminarFoto($id_usuario);
        $mensaje = $res['mensaje'];
        $mensaje_tipo = $res['error'] ? 'danger' : 'success';
    }
}

$perfil = $configController->obtenerPerfil($id_usuario);
?>

<!DOCTYPE html>
<html lang="es">
<?php if (!defined('EMBEDDED_VIEW')): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Profesor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <link rel="stylesheet" href="../../Public/css/configuracion.css">
    <link rel="stylesheet" href="../../Public/css/swal-custom.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php endif; ?>

<link rel="stylesheet" href="../../Public/css/configuracion.css">

<div class="config-container">
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $mensaje_tipo ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header del perfil -->
    <div class="profile-header">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div>
                <form method="POST" enctype="multipart/form-data" id="formFoto">
                    <input type="file" name="foto_perfil" id="inputFoto" accept="image/*" style="display:none;">
                    <?php if (!empty($perfil['foto_perfil'])): ?>
                        <img src="../../Public/<?= htmlspecialchars($perfil['foto_perfil']) ?>" 
                             alt="Foto de perfil" 
                             class="profile-avatar"
                             onclick="document.getElementById('inputFoto').click()">
                    <?php else: ?>
                        <div class="avatar-placeholder" onclick="document.getElementById('inputFoto').click()">
                            👤
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-1 fw-bold"><?= htmlspecialchars($perfil['nombre']) ?></h2>
                <p class="mb-2 opacity-75">
                    <?= htmlspecialchars($perfil['correo']) ?>
                    <?php if (!empty($perfil['nuevo_correo'])): ?>
                        <br><small class="text-warning">Pendiente de confirmación: <?= htmlspecialchars($perfil['nuevo_correo']) ?></small>
                    <?php endif; ?>
                </p>
                <span class="badge-role badge-profesor">👨‍🏫 Profesor</span>
            </div>
        </div>
    </div>

    <!-- Información Personal -->
    <div class="config-section">
        <h4>📋 Mi Información</h4>
        <form method="POST" id="form-actualizar-datos">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" 
                           value="<?= htmlspecialchars($perfil['nombre']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Correo Electrónico</label>
                    <input type="email" name="correo" class="form-control" 
                           value="<?= htmlspecialchars($perfil['correo']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" 
                           value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" 
                           placeholder="+51987654321">
                    <small class="text-muted">Requerido para verificación en reservas y préstamos</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Última actualización</label>
                    <input type="text" class="form-control" 
                           value="<?= $perfil['fecha_actualizacion'] ? date('d/m/Y H:i', strtotime($perfil['fecha_actualizacion'])) : 'Sin registros' ?>" 
                           disabled>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Biografía / Notas</label>
                    <textarea name="bio" class="form-control" rows="3" 
                              placeholder="Información adicional sobre ti, especialidad, etc..."><?= htmlspecialchars($perfil['bio'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="actualizar_datos" class="btn btn-brand" id="btn-guardar-datos">
                    💾 Guardar Cambios
                </button>
            </div>
        </form>
        
        <?php if (!empty($perfil['foto_perfil'])): ?>
            <div class="mt-2">
                <button type="button" class="btn btn-outline-danger"
                        onclick="confirmarEliminarFoto()">
                    🗑️ Eliminar Foto
                </button>
            </div>
            <form method="POST" id="formEliminarFoto" style="display:none;">
                <input type="hidden" name="eliminar_foto" value="1">
            </form>
        <?php endif; ?>
    </div>

    <!-- Datos de la cuenta -->
    <div class="config-section">
        <h4>🔐 Información de Cuenta</h4>
        <div class="info-row">
            <div class="info-label">Estado de verificación</div>
            <div class="info-value">
                <?php if ($perfil['verificado']): ?>
                    <span class="badge bg-success">✅ Cuenta verificada</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">⚠️ Pendiente de verificación</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <a href="Profesor.php?view=password" class="btn btn-outline-brand">
                🔑 Cambiar Contraseña
            </a>
        </div>
    </div>

    <!-- Acceso rápido -->
    <div class="config-section">
        <h4>⚡ Acceso Rápido</h4>
        <div class="admin-actions-grid">
            <a href="Profesor.php?view=reserva" class="action-card">
                <div class="action-card-icon">📅</div>
                <div class="action-card-title">Reservar Aula</div>
                <p class="action-card-desc">Consultar disponibilidad y reservar</p>
            </a>
            <a href="Profesor.php?view=prestamo" class="action-card">
                <div class="action-card-icon">💻</div>
                <div class="action-card-title">Préstamo Equipos</div>
                <p class="action-card-desc">Solicitar equipos disponibles</p>
            </a>
            <a href="Profesor.php?view=historial" class="action-card">
                <div class="action-card-icon">📄</div>
                <div class="action-card-title">Mis Reservas</div>
                <p class="action-card-desc">Ver mi historial</p>
            </a>
            <a href="Profesor.php?view=tommibot" class="action-card">
                <div class="action-card-icon">🤖</div>
                <div class="action-card-title">TommiBot IA</div>
                <p class="action-card-desc">Asistente virtual inteligente</p>
            </a>
        </div>
    </div>

    <!-- Tips para profesores -->
    <div class="config-section">
        <h4>💡 Consejos</h4>
        <ul class="mb-0">
            <li class="mb-2">✅ Mantén tu teléfono actualizado y verificado para agilizar reservas</li>
            <li class="mb-2">📧 Verifica que tu correo esté activo para recibir notificaciones</li>
            <li class="mb-2">🔔 Revisa tu historial regularmente para gestionar tus reservas</li>
            <li>🤖 Usa TommiBot para consultas rápidas sobre el sistema</li>
        </ul>
    </div>
</div>

<script>
// Confirmación antes de guardar cambios
document.getElementById('form-actualizar-datos')?.addEventListener('submit', function(e){
    if (window.__savingProfile) return;
    if (typeof Swal === 'undefined') { return; }
    e.preventDefault();
    Swal.fire({
        title: '¿Guardar cambios del perfil?',
        text: 'Se actualizarán tus datos personales.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then(res => { if (res.isConfirmed){ window.__savingProfile = true; const f=e.target; if(!f.querySelector('input[name="actualizar_datos"]')){ const h=document.createElement('input'); h.type='hidden'; h.name='actualizar_datos'; h.value='1'; f.appendChild(h);} f.submit(); } });
});

// Mostrar mensaje de éxito/error automáticamente
<?php if ($mensaje && isset($_POST['eliminar_foto'])): ?>
Swal.fire({
    icon: '<?= $mensaje_tipo === 'success' ? 'success' : 'error' ?>',
    title: '<?= $mensaje_tipo === 'success' ? '¡Foto eliminada!' : 'Error' ?>',
    text: '<?= addslashes($mensaje) ?>',
    timer: 3000,
    showConfirmButton: true
});
<?php endif; ?>

<?php if ($mensaje && isset($_POST['actualizar_datos'])): ?>
Swal.fire({
    icon: '<?= $mensaje_tipo === 'success' ? 'success' : 'error' ?>',
    title: '<?= $mensaje_tipo === 'success' ? '¡Guardado!' : 'Error' ?>',
    text: '<?= addslashes($mensaje) ?>',
    timer: 3000,
    showConfirmButton: true
});
<?php endif; ?>

// Auto-submit al seleccionar foto
document.getElementById('inputFoto')?.addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('formFoto').submit();
    }
});

function confirmarEliminarFoto() {
    Swal.fire({
        title: '¿Eliminar foto de perfil?',
        text: 'Tu foto de perfil actual será eliminada y volverá al avatar predeterminado',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formEliminarFoto').submit();
        }
    });
}
</script>

<?php if (!defined('EMBEDDED_VIEW')): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
<?php endif; ?>
