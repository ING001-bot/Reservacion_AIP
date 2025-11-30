<?php
// Configuración - Vista para Administrador
if (!defined('EMBEDDED_VIEW')) {
    session_start();
    if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'Administrador') {
        header('Location: ../../Public/index.php');
        exit;
    }
}

require_once __DIR__ . '/../controllers/ConfiguracionController.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../controllers/SistemaController.php';

$id_usuario = $_SESSION['id_usuario'] ?? 0;
$configController = new ConfiguracionController();
$usuarioModel = new UsuarioModel();
$sistemaController = new SistemaController();

$mensaje = '';
$mensaje_tipo = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ejecutar_mantenimiento'])) {
        $res = $sistemaController->ejecutarMantenimientoCompleto($id_usuario);
        $mensaje = $res['mensaje'];
        if (!$res['error'] && isset($res['detalles'])) {
            $mensaje .= '<br><small>' . implode('<br>', $res['detalles']) . '</small>';
        }
        $mensaje_tipo = $res['error'] ? 'danger' : 'success';
    }
    
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

    if (isset($_POST['cambiar_rol'])) {
        $res = $configController->cambiarRol($_POST['id_usuario_rol'], $_POST['nuevo_rol']);
        $mensaje = $res['mensaje'];
        $mensaje_tipo = $res['error'] ? 'danger' : 'success';
    }
}

$perfil = $configController->obtenerPerfil($id_usuario);
$todosUsuarios = $usuarioModel->obtenerUsuarios();
$mantenimientoInfo = $sistemaController->obtenerUltimoMantenimiento();
?>

<!DOCTYPE html>
<html lang="es">
<?php if (!defined('EMBEDDED_VIEW')): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <link rel="stylesheet" href="../../Public/css/configuracion.css">
    <link rel="stylesheet" href="../../Public/css/swal-custom.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../Public/css/configuracion.css">
<link rel="stylesheet" href="../../Public/css/swal-custom.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/alerts.js"></script>

<div class="config-container">
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $mensaje_tipo ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
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
                <p class="mb-2 opacity-75"><?= htmlspecialchars($perfil['correo']) ?></p>
                <span class="badge-role badge-admin">🔐 Administrador</span>
            </div>
        </div>
    </div>

    <!-- Información Personal -->
    <div class="config-section">
        <h4>📋 Información Personal</h4>
        <form method="POST">
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
                              placeholder="Información adicional sobre ti..."><?= htmlspecialchars($perfil['bio'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="actualizar_datos" class="btn btn-brand">
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

    <!-- Mantenimiento del Sistema -->
    <div class="config-section">
        <h4><i class="fas fa-tools me-2"></i> Mantenimiento del Sistema</h4>
        
        <?php 
        // Mostrar alerta SOLO si ya se ejecutó antes Y han pasado 30 días
        $mostrarAlerta = $mantenimientoInfo['ejecutado'] && $mantenimientoInfo['puede_ejecutar'];
        ?>
        
        <?php if ($mostrarAlerta): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Disponible:</strong> Han pasado 30 días desde el último mantenimiento. Es recomendable ejecutarlo.
        </div>
        <?php endif; ?>
        
        <?php if ($mantenimientoInfo['ejecutado']): ?>
        <div class="mb-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Último Mantenimiento</h6>
                            <p class="mb-0 fw-bold"><?= date('d/m/Y H:i', strtotime($mantenimientoInfo['fecha'])) ?></p>
                            <small class="text-muted">Por: <?= htmlspecialchars($mantenimientoInfo['ejecutado_por']) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-<?= $mantenimientoInfo['puede_ejecutar'] ? 'success' : 'secondary' ?>">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Próximo Disponible</h6>
                            <?php if ($mantenimientoInfo['puede_ejecutar']): ?>
                                <p class="mb-0 fw-bold text-success"><i class="fas fa-check-circle me-1"></i> Disponible Ahora</p>
                            <?php else: ?>
                                <p class="mb-0 fw-bold">En <?= $mantenimientoInfo['dias_restantes'] ?> días</p>
                                <small class="text-muted"><?= $mantenimientoInfo['dias_transcurridos'] ?> de 30 días transcurridos</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="formMantenimiento">
            <input type="hidden" name="ejecutar_mantenimiento" value="1">
            <button type="button" 
                    class="btn btn-lg btn-<?= ($mantenimientoInfo['puede_ejecutar'] ?? true) ? 'primary' : 'secondary' ?> w-100" 
                    id="btnMantenimiento"
                    <?= (!($mantenimientoInfo['puede_ejecutar'] ?? true) && $mantenimientoInfo['ejecutado']) ? 'disabled' : '' ?>
                    onclick="confirmarMantenimiento()">
                <i class="fas fa-sync-alt me-2"></i>
                <?= (!($mantenimientoInfo['puede_ejecutar'] ?? true) && $mantenimientoInfo['ejecutado']) 
                    ? 'Mantenimiento No Disponible (Esperar ' . $mantenimientoInfo['dias_restantes'] . ' días)' 
                    : 'Ejecutar Mantenimiento Completo' ?>
            </button>
        </form>
        
        <div class="mt-3">
            <small class="text-muted">
                <strong>El mantenimiento incluye:</strong><br>
                • Optimización de base de datos<br>
                • Limpieza de notificaciones antiguas (+3 meses)<br>
                • Generación de backup automático<br>
                • Limpieza de sesiones caducadas<br>
                • Recálculo de estadísticas
            </small>
        </div>
    </div>

    <!-- Estadísticas del Sistema -->
    <div class="config-section">
        <h4>📊 Estadísticas del Sistema</h4>
        <div id="estadisticas-container">
            <div class="text-center py-3">
                <div class="spinner-border text-brand" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestión de Backups -->
    <div class="config-section">
        <h4>💾 Gestión de Backups</h4>
        <p class="text-muted mb-3">Crea copias de seguridad de la base de datos para prevenir pérdida de información</p>
        
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button type="button" class="btn btn-brand" id="btnCrearBackup">
                <i class="bi bi-save"></i> Crear Backup Completo
            </button>
            <button type="button" class="btn btn-outline-primary" id="btnBackupAuto">
                <i class="bi bi-clock-history"></i> Backup Rápido (Críticas)
            </button>
            <button type="button" class="btn btn-outline-warning" id="btnLimpiarBackups">
                <i class="bi bi-trash"></i> Limpiar Antiguos
            </button>
        </div>

        <div id="backups-container">
            <div class="text-center py-3">
                <div class="spinner-border text-brand" role="status">
                    <span class="visually-hidden">Cargando backups...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actualizaciones del Chatbot Tommibot -->
    <div class="config-section">
        <h4>🤖 Actualizaciones del Chatbot Tommibot</h4>
        <div class="alert alert-info mb-3" style="font-size:1.05em;">
            <b>¿Qué es esto?</b> Aquí puedes actualizar el chatbot mensualmente, ver el historial de cambios y activar la voz.<br>
            <ul class="mb-0 ps-4">
                <li>Cada actualización mensual agrega nuevas funciones, preguntas y respuestas inteligentes.</li>
                <li id="changelog-desc"></li>
                <li>Activa la <b>lectura por voz (TTS)</b> para que el chatbot lea sus respuestas.</li>
            </ul>
        </div>
        <form method="POST" class="mb-3 d-flex align-items-center gap-3 flex-wrap">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="toggleTTS" name="toggle_tts" value="1" <?= isset($_SESSION['tts_enabled']) && $_SESSION['tts_enabled'] ? 'checked' : '' ?> onclick="mostrarDemoTTS()">
                <label class="form-check-label" for="toggleTTS">Activar lectura por voz (TTS) en respuestas del chatbot</label>
            </div>
            <div class="w-100">
                <small class="text-muted">
                    <b>¿Qué es TTS?</b> TTS significa <b>Text-to-Speech</b> (texto a voz). Si activas esta opción, el chatbot leerá en voz alta sus respuestas, facilitando la accesibilidad y permitiendo que escuches la información sin necesidad de leerla.
                </small>
            </div>
            <button type="button" class="btn btn-success" id="btnActualizarChatbot" onclick="actualizarChatbotReal()" disabled>
                <i class="bi bi-arrow-repeat"></i> Actualizar Chatbot ahora
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="mostrarChangelog()">
                <i class="bi bi-clock-history"></i> Ver historial de actualizaciones (changelog)
            </button>
        </form>
        <div id="alerta-actualizacion" class="alert alert-warning d-none"></div>
        <div class="mb-2">
            <span class="text-muted">¿Tienes dudas? Pregúntale a Tommibot: <b>¿Cómo se actualiza el chatbot?</b>, <b>¿Qué trae la nueva actualización?</b>, <b>¿Para qué sirve la actualización mensual?</b>, <b>¿Cómo activo la voz?</b> y más.</span>
        </div>
        <div id="changelog-modal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-brand text-white">
                        <h5 class="modal-title">📝 Historial de actualizaciones del Chatbot</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="changelog-body">
                        <div class="text-center py-3">
                            <div class="spinner-border text-brand" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones de Administrador -->
    <div class="config-section">
        <h4>⚙️ Acciones de Configuración</h4>
        <script>
        // Mostrar texto correcto sobre el changelog según si hay registros
        window.addEventListener('DOMContentLoaded', function() {
            let changelog = [];
            try {
                changelog = JSON.parse(localStorage.getItem('tommibot_changelog')||'[]');
            } catch(e) { changelog = []; }
            const desc = document.getElementById('changelog-desc');
            if (desc) {
                if (changelog.length > 0) {
                    desc.innerHTML = 'Puedes consultar el <b>historial de actualizaciones</b> para ver qué trae cada versión.';
                } else {
                    desc.innerHTML = 'El historial de actualizaciones aparecerá aquí cuando se realice la primera actualización.';
                }
            }
        });
        // Mostrar changelog en modal
        function mostrarChangelog() {
            const modal = new bootstrap.Modal(document.getElementById('changelog-modal'));
            const body = document.getElementById('changelog-body');
            body.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-brand" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            fetch('../../app/lib/tommibot_changelog.php')
                .then(r => r.text())
                .then(txt => {
                    let cambios = [];
                    try {
                        const arr = txt.match(/return \[(.*)\];/s);
                        if (arr && arr[1]) {
                            const json = arr[1]
                                .replace(/'version'/g, '"version"')
                                .replace(/'fecha'/g, '"fecha"')
                                .replace(/'cambios'/g, '"cambios"')
                                .replace(/'/g, '"');
                            cambios = JSON.parse('[' + json + ']');
                        }
                    } catch(e) { body.innerHTML = '<div class="alert alert-danger">No se pudo cargar el changelog.</div>'; return; }
                    if (!cambios.length) { body.innerHTML = '<div class="alert alert-info">No hay actualizaciones registradas.</div>'; return; }
                    let html = '<ul class="list-group">';
                    cambios.forEach(ver => {
                        html += `<li class="list-group-item">
                            <strong>Versión ${ver.version}</strong> <span class="text-muted">(${ver.fecha})</span>
                            <ul>`;
                        ver.cambios.forEach(c => { html += `<li>${c}</li>`; });
                        html += '</ul></li>';
                    });
                    html += '</ul>';
                    body.innerHTML = html;
                })
                .catch(() => { body.innerHTML = '<div class="alert alert-danger">No se pudo cargar el changelog.</div>'; });
            modal.show();
        }

        // Simular actualización mensual del chatbot
        function actualizarChatbot() {
            const btn = document.getElementById('btnActualizarChatbot');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Actualizando...';
            setTimeout(() => {
                // Simular nueva versión
                const now = new Date();
                const version = `${now.getFullYear()}.${(now.getMonth()+1).toString().padStart(2,'0')}`;
                const fecha = now.toISOString().slice(0,10);
                const nuevasFunciones = [
                    'Reconocimiento de nuevas preguntas frecuentes',
                    'Mejoras en la navegación por comandos',
                    'Respuestas más detalladas y personalizadas',
                    'Mayor seguridad y estabilidad',
                    'Soporte para nuevas funciones administrativas',
                ];
                // Guardar en localStorage (simulación, en producción sería por backend)
                let changelog = JSON.parse(localStorage.getItem('tommibot_changelog')||'[]');
                changelog.unshift({version,fecha,cambios:nuevasFunciones});
                localStorage.setItem('tommibot_changelog',JSON.stringify(changelog));
                btn.innerHTML = '<i class="bi bi-check-circle"></i> ¡Actualizado!';
                setTimeout(()=>{btn.innerHTML='<i class="bi bi-arrow-repeat"></i> Actualizar Chatbot ahora';btn.disabled=false;},2000);
                Swal.fire({
                    icon:'success',
                    title:'¡Chatbot actualizado!',
                    html:`<b>Versión ${version}</b> instalada.<br>Funciones nuevas:<ul style='text-align:left;'>${nuevasFunciones.map(f=>`<li>${f}</li>`).join('')}</ul>`,
                    confirmButtonText:'Ver changelog',
                    showCancelButton:true,
                    cancelButtonText:'Cerrar'
                }).then(r=>{if(r.isConfirmed)mostrarChangelog();});
            }, 1800);
        }
        </script>
        <div class="admin-actions-grid">
            <a href="Admin.php?view=usuarios" class="action-card">
                <div class="action-card-icon">👥</div>
                <div class="action-card-title">Gestionar Usuarios</div>
                <p class="action-card-desc">Crear, editar y eliminar usuarios del sistema</p>
            </a>
            <div class="action-card" data-bs-toggle="modal" data-bs-target="#modalCambiarRol">
                <div class="action-card-icon">🔄</div>
                <div class="action-card-title">Cambiar Roles</div>
                <p class="action-card-desc">Asignar o modificar roles de usuarios</p>
            </div>
            <a href="Admin.php?view=aulas" class="action-card">
                <div class="action-card-icon">🏫</div>
                <div class="action-card-title">Gestionar Aulas</div>
                <p class="action-card-desc">Administrar aulas de innovación</p>
            </a>
            <a href="Admin.php?view=equipos" class="action-card">
                <div class="action-card-icon">💻</div>
                <div class="action-card-title">Inventario Equipos</div>
                <p class="action-card-desc">Control de equipos disponibles</p>
            </a>
            <a href="Admin.php?view=password" class="action-card">
                <div class="action-card-icon">🔑</div>
                <div class="action-card-title">Cambiar Contraseña</div>
                <p class="action-card-desc">Actualizar credenciales de acceso</p>
            </a>
            <a href="Admin.php?view=reportes" class="action-card">
                <div class="action-card-icon">📊</div>
                <div class="action-card-title">Reportes</div>
                <p class="action-card-desc">Estadísticas y análisis del sistema</p>
            </a>
        </div>
    </div>
</div>

<!-- Modal Cambiar Rol -->
<div class="modal fade" id="modalCambiarRol" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-brand text-white">
                <h5 class="modal-title">🔄 Cambiar Rol de Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seleccionar Usuario</label>
                        <select name="id_usuario_rol" class="form-select" required>
                            <option value="">-- Elegir usuario --</option>
                            <?php foreach ($todosUsuarios as $u): ?>
                                <option value="<?= $u['id_usuario'] ?>">
                                    <?= htmlspecialchars($u['nombre']) ?> - 
                                    <?= htmlspecialchars($u['correo']) ?> 
                                    (<?= htmlspecialchars($u['tipo_usuario']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo Rol</label>
                        <select name="nuevo_rol" class="form-select" required>
                            <option value="Profesor">👨‍🏫 Profesor</option>
                            <option value="Encargado">🧰 Encargado</option>
                            <option value="Administrador">🔐 Administrador</option>
                        </select>
                    </div>
                    <div class="alert alert-warning mb-0">
                        ⚠️ El cambio de rol afectará los permisos del usuario inmediatamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="cambiar_rol" class="btn btn-brand">Cambiar Rol</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// --- Control de actualización mensual real ---
const btnActualizar = document.getElementById('btnActualizarChatbot');
const alertaActualizacion = document.getElementById('alerta-actualizacion');
let now = new Date();
let lastUpdate = localStorage.getItem('tommibot_last_update');
// Si nunca se ha actualizado, inicializar la fecha desde hoy
if (!lastUpdate) {
    localStorage.setItem('tommibot_last_update', now.toISOString());
    lastUpdate = now.toISOString();
}
let lastDate = new Date(lastUpdate);
let diffDays = Math.floor((now - lastDate) / (1000*60*60*24));
if(diffDays >= 30) {
    btnActualizar.disabled = false;
    alertaActualizacion.classList.remove('d-none');
    alertaActualizacion.innerHTML = '<b>¡Actualización disponible!</b> Han pasado más de 30 días desde la última actualización del chatbot. Haz clic en "Actualizar Chatbot ahora" para instalar las nuevas funciones.';
    // Notificación tipo alerta (puedes mejorar con tu sistema de notificaciones)
    if(!localStorage.getItem('tommibot_alerta_mostrada_'+now.toISOString().slice(0,7))) {
        Swal.fire({
            icon:'info',
            title:'Actualización mensual disponible',
            html:'Han pasado más de 30 días desde la última actualización del chatbot. ¡Instala las nuevas funciones ahora!',
            confirmButtonText:'Entendido'
        });
        localStorage.setItem('tommibot_alerta_mostrada_'+now.toISOString().slice(0,7), '1');
    }
} else {
    btnActualizar.disabled = true;
    alertaActualizacion.classList.add('d-none');
}

// --- Confirmación tipo mantenimiento y actualización real ---
function actualizarChatbotReal() {
    Swal.fire({
        icon:'question',
        title:'¿Actualizar Chatbot?',
        html:'¿Deseas instalar la actualización mensual del chatbot?<br><b>Esta versión incluye:</b><ul style="text-align:left"><li>Lectura por voz (TTS) activada</li><li>Reconocimiento de nuevas preguntas frecuentes</li><li>Mejoras en la navegación por comandos</li><li>Respuestas más detalladas y personalizadas</li><li>Mayor seguridad y estabilidad</li><li>Soporte para nuevas funciones administrativas</li></ul>',
        showCancelButton:true,
        confirmButtonText:'Sí, actualizar',
        cancelButtonText:'Cancelar',
        focusConfirm:true
    }).then(r=>{
        if(r.isConfirmed) {
            btnActualizar.disabled = true;
            btnActualizar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Actualizando...';
            fetch('../../app/api/actualizar_chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.mensaje || 'No se pudo actualizar el chatbot.' });
                    btnActualizar.disabled = false;
                    btnActualizar.innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar Chatbot ahora';
                    return;
                }
                // Actualización exitosa: refrescar changelog y estado
                Swal.fire({
                    icon:'success',
                    title:'¡Chatbot actualizado!',
                    html:`<b>Versión ${data.version}</b> instalada.<br>Todas las funciones nuevas están activas.`,
                    confirmButtonText:'Ver changelog',
                    showCancelButton:true,
                    cancelButtonText:'Cerrar'
                }).then(r=>{if(r.isConfirmed)mostrarChangelog();});
                btnActualizar.innerHTML = '<i class="bi bi-check-circle"></i> ¡Actualizado!';
                setTimeout(()=>{btnActualizar.innerHTML='<i class="bi bi-arrow-repeat"></i> Actualizar Chatbot ahora';},2000);
                // Opcional: recargar la página para mostrar nuevas funciones
                setTimeout(()=>{location.reload();}, 2200);
            })
            .catch(()=>{
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' });
                btnActualizar.disabled = false;
                btnActualizar.innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar Chatbot ahora';
            });
        }
    });
}
// Auto-submit al seleccionar foto
document.getElementById('inputFoto')?.addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('formFoto').submit();
    }
});

// Cargar estadísticas al iniciar
cargarEstadisticas();
cargarBackups();

function cargarEstadisticas() {
    fetch('../api/estadisticas.php', {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                console.error('Error del servidor:', data.mensaje);
                document.getElementById('estadisticas-container').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar estadísticas: ' + (data.mensaje || 'Desconocido') + '</div>';
                return;
            }
            
            const stats = data.estadisticas;
            document.getElementById('estadisticas-container').innerHTML = `
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-value">${stats.total}</div>
                            <div class="stat-label">Total Usuarios</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">🔐</div>
                            <div class="stat-value">${stats.administradores}</div>
                            <div class="stat-label">Administradores</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">🧰</div>
                            <div class="stat-value">${stats.encargados}</div>
                            <div class="stat-label">Encargados</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon">👨‍🏫</div>
                            <div class="stat-value">${stats.profesores}</div>
                            <div class="stat-label">Profesores</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-value">${stats.verificados}</div>
                            <div class="stat-label">Verificados (Email)</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">📅</div>
                            <div class="stat-value">${stats.reservas_activas || 0}</div>
                            <div class="stat-label">Reservas Activas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">📈</div>
                            <div class="stat-value">${Math.round((stats.verificados / stats.total) * 100)}%</div>
                            <div class="stat-label">Tasa Verificación</div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            console.error('Error:', err);
            document.getElementById('estadisticas-container').innerHTML = 
                '<div class="alert alert-danger">⚠️ Error al cargar estadísticas</div>';
        });
}

function cargarBackups() {
    fetch('../api/backup.php?action=listar')
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('backups-container').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar backups</div>';
                return;
            }
            
            const backups = data.backups;
            if (backups.length === 0) {
                document.getElementById('backups-container').innerHTML = 
                    '<div class="alert alert-info">No hay backups disponibles. Crea el primero.</div>';
                return;
            }
            
            let html = '<div class="table-responsive"><table class="table table-hover">';
            html += '<thead><tr><th>Archivo</th><th>Fecha</th><th>Tamaño</th><th>Acciones</th></tr></thead><tbody>';
            
            backups.forEach(backup => {
                html += `<tr>
                    <td><i class="bi bi-file-earmark-zip"></i> ${backup.nombre}</td>
                    <td>${backup.fecha}</td>
                    <td>${backup.tamaño}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="descargarBackup('${backup.nombre}')">
                            <i class="bi bi-download"></i> Descargar
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="restaurarBackup('${backup.nombre}')">
                            <i class="bi bi-arrow-clockwise"></i> Restaurar
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarBackup('${backup.nombre}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            document.getElementById('backups-container').innerHTML = html;
        })
        .catch(err => {
            console.error('Error:', err);
            document.getElementById('backups-container').innerHTML = 
                '<div class="alert alert-danger">⚠️ Error al cargar lista de backups</div>';
        });
}

// Crear backup completo
document.getElementById('btnCrearBackup')?.addEventListener('click', async function() {
    const confirm = await showConfirm(
        '¿Crear backup completo?',
        'Se creará una copia de seguridad de toda la base de datos',
        'Sí, crear backup'
    );
    
    if (!confirm.isConfirmed) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
    showLoading('Creando backup', 'Este proceso puede tardar unos segundos...');
    
    fetch('../api/backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=crear'
    })
    .then(r => r.json())
    .then(data => {
        closeLoading();
        if (data.error) {
            showError('Error al crear backup', data.mensaje);
        } else {
            showSuccess('Backup creado exitosamente', data.mensaje, 4000);
            cargarBackups();
        }
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-save"></i> Crear Backup Completo';
    })
    .catch(err => {
        closeLoading();
        showError('Error al crear backup', 'Ocurrió un error inesperado');
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-save"></i> Crear Backup Completo';
    });
});

// Backup automático
document.getElementById('btnBackupAuto')?.addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
    showLoading('Creando backup rápido', 'Solo tablas críticas...');
    
    fetch('../api/backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=crear'
    })
    .then(r => r.json())
    .then(data => {
        closeLoading();
        if (data.error) {
            showError('Error', data.mensaje);
        } else {
            toastSuccess(data.mensaje);
            cargarBackups();
        }
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-clock-history"></i> Backup Rápido (Críticas)';
    });
});

// Limpiar backups antiguos
document.getElementById('btnLimpiarBackups')?.addEventListener('click', async function() {
    const confirm = await showWarning(
        '¿Eliminar backups antiguos?',
        'Se mantendrán los últimos 10 backups. Los más antiguos serán eliminados permanentemente.'
    );
    
    if (!confirm.isConfirmed) return;
    
    showLoading('Limpiando backups...');
    
    fetch('../api/backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=limpiar&mantener=10'
    })
    .then(r => r.json())
    .then(data => {
        closeLoading();
        if (data.error) {
            showError('Error', data.mensaje);
        } else {
            toastSuccess(data.mensaje);
        }
        cargarBackups();
    });
});

function descargarBackup(filename) {
    window.location.href = `../api/backup.php?action=descargar&filename=${encodeURIComponent(filename)}`;
}

async function restaurarBackup(filename) {
    const confirm = await showDoubleConfirm(
        '⚠️ ADVERTENCIA: Restaurar Backup',
        'Esto sobrescribirá TODA la base de datos actual con los datos del backup. Esta acción no se puede deshacer.',
        'Sí, restaurar ahora'
    );
    
    if (!confirm.isConfirmed) return;
    
    showLoading('Restaurando backup', 'Este proceso puede tardar varios segundos. NO cierres esta ventana.');
    
    fetch('../api/backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=restaurar&filename=${encodeURIComponent(filename)}`
    })
    .then(r => r.json())
    .then(async data => {
        closeLoading();
        if (data.error) {
            showError('Error al restaurar', data.mensaje);
        } else {
            await showSuccess('Backup restaurado', 'La base de datos ha sido restaurada. La página se recargará.', 3000);
            setTimeout(() => location.reload(), 3000);
        }
    })
    .catch(err => {
        closeLoading();
        showError('Error crítico', 'Ocurrió un error al restaurar el backup. Verifica el estado de la base de datos.');
    });
}

async function eliminarBackup(filename) {
    const confirm = await showDangerConfirm(
        '¿Eliminar este backup?',
        `El archivo ${filename} será eliminado permanentemente.`,
        'Sí, eliminar'
    );
    
    if (!confirm.isConfirmed) return;
    
    fetch('../api/backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=eliminar&filename=${encodeURIComponent(filename)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showError('Error', data.mensaje);
        } else {
            toastSuccess('Backup eliminado');
            cargarBackups();
        }
    });
}

// Confirmar eliminar foto
async function confirmarEliminarFoto() {
    const confirm = await showDangerConfirm(
        '¿Eliminar foto de perfil?',
        'Tu foto de perfil actual será eliminada y volverá al avatar predeterminado',
        'Sí, eliminar'
    );
    
    if (confirm.isConfirmed) {
        let form = document.getElementById('formEliminarFoto');
        if (!form) {
            // Crear el formulario dinámicamente si no existe
            form = document.createElement('form');
            form.method = 'POST';
            form.id = 'formEliminarFoto';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'eliminar_foto';
            input.value = '1';
            form.appendChild(input);
            document.body.appendChild(form);
        }
        form.submit();
    }
}

// Función para confirmar mantenimiento del sistema
async function confirmarMantenimiento() {
    const confirm = await showDoubleConfirm(
        '🔧 Mantenimiento del Sistema',
        '<div class="text-start">' +
        '<p class="mb-3">Se ejecutará un mantenimiento completo que incluye:</p>' +
        '<ul class="list-unstyled ps-3">' +
        '<li class="mb-2"><i class="fas fa-database text-primary me-2"></i> Optimización de base de datos</li>' +
        '<li class="mb-2"><i class="fas fa-bell-slash text-warning me-2"></i> Limpieza de notificaciones antiguas</li>' +
        '<li class="mb-2"><i class="fas fa-save text-success me-2"></i> Generación de backup automático</li>' +
        '<li class="mb-2"><i class="fas fa-broom text-info me-2"></i> Limpieza de sesiones</li>' +
        '<li class="mb-2"><i class="fas fa-chart-line text-danger me-2"></i> Recálculo de estadísticas</li>' +
        '</ul>' +
        '<div class="alert alert-warning mt-3 mb-0">' +
        '<i class="fas fa-clock me-2"></i><strong>Nota:</strong> Este proceso puede tardar varios minutos.' +
        '</div>' +
        '</div>',
        'Sí, ejecutar ahora'
    );
    
    if (!confirm.isConfirmed) return;
    
    showLoading('Ejecutando Mantenimiento', 'Por favor espere, esto puede tardar varios minutos...');
    
    // Deshabilitar el botón
    const btn = document.getElementById('btnMantenimiento');
    btn.disabled = true;
    
    // Enviar el formulario
    document.getElementById('formMantenimiento').submit();

    }

    // --- Demo TTS sin recarga ---
    function mostrarDemoTTS() {
        const ttsSwitch = document.getElementById('toggleTTS');
        if (ttsSwitch.checked) {
            Swal.fire({
                icon:'info',
                title:'Lectura por voz activada (demo)',
                text:'Esta opción es solo de muestra. Cuando la actualización esté disponible, el chatbot leerá sus respuestas en voz alta.',
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon:'info',
                title:'Lectura por voz desactivada (demo)',
                text:'La lectura por voz está desactivada. Esta opción es solo de muestra.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    </script>
    </body>
    </html>
