<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$rol = $_SESSION['tipo'] ?? null;
$esAdmin = ($rol === 'Administrador');

// Bloquear registro público si la instalación ya fue completada
require_once __DIR__ . '/../config/conexion.php';
$setupCompleted = '0';
try {
    $stmtCfg = $conexion->prepare("SELECT cfg_value FROM app_config WHERE cfg_key='setup_completed'");
    $stmtCfg->execute();
    $setupCompleted = (string)($stmtCfg->fetchColumn() ?: '0');
} catch (\Throwable $e) { error_log('setup_completed read failed: ' . $e->getMessage()); }
if (!$esAdmin && $setupCompleted === '1') { header('Location: ../../Public/index.php'); exit(); }

require '../controllers/UsuarioController.php';
$controller = new UsuarioController();
$data = $controller->handleRequest();

$usuarios = $data['usuarios'];
$mensaje = $data['mensaje'];
$mensaje_tipo = $data['mensaje_tipo'];
$id_editar = $_GET['editar'] ?? null; // Para edición inline
?>

<?php if (!defined('EMBEDDED_VIEW')): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>👤 <?= $esAdmin ? "Usuarios" : "Crear Cuenta" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
  <main class="container py-4">
<?php endif; ?>

<h1 class="mb-4 text-brand">👤 <?= $esAdmin ? "Gestión de Usuarios" : "Crear Cuenta" ?></h1>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?= htmlspecialchars($mensaje_tipo) ?> alert-dismissible fade show shadow-sm" role="alert">
    <?= htmlspecialchars($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if ($esAdmin): ?>
<!-- Formulario Admin -->
<div class="card card-brand shadow-sm mb-4">
    <div class="card-header bg-brand text-white">Registrar Usuario</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="admin-name" class="form-control" required>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
<<<<<<< HEAD
                <label class="form-label">Teléfono (con código de país)</label>
                <input type="tel" name="telefono" class="form-control" placeholder="+51987654321">
=======
                <label class="form-label">Teléfono</label>
                <input type="tel" name="telefono" class="form-control" placeholder="+519XXXXXXXX">
>>>>>>> 37d623eb911e485d34ce66af60d357b7fdb58415
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label">Contraseña</label>
                <div class="password-field">
                    <input type="password" name="contraseña" id="admin-pass" class="form-control" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('admin-pass')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <label class="form-label">Tipo de Usuario</label>
                <select name="tipo" class="form-select" required>
                    <option value="">-- Selecciona un tipo --</option>
                    <option value="Profesor">Profesor</option>
                    <option value="Encargado">Encargado</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" name="registrar_usuario_admin" class="btn btn-brand w-100">Registrar Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla usuarios -->
<div class="card card-brand shadow-sm">
    <div class="card-header bg-brand text-white">Usuarios Registrados</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle table-brand">
                <thead class="table-primary text-center">
                    <tr>
                        <th class="col-num">N°</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Tipo</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($usuarios as $user): ?>
<<<<<<< HEAD
                    <?php if ($id_editar == $user['id_usuario']): ?>
                        <form method="post">
                        <tr>
                            <td class="col-num"><?= $i ?></td>
                            <td><input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" class="form-control" required></td>
                            <td><input type="email" name="correo" value="<?= htmlspecialchars($user['correo']) ?>" class="form-control" required></td>
                            <td><input type="tel" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" class="form-control" placeholder="+51987654321"></td>
                            <td>
                                <select name="tipo" class="form-select" required>
                                    <option value="Profesor" <?= $user['tipo_usuario']=='Profesor'?'selected':'' ?>>Profesor</option>
                                    <option value="Encargado" <?= $user['tipo_usuario']=='Encargado'?'selected':'' ?>>Encargado</option>
                                    <option value="Administrador" <?= $user['tipo_usuario']=='Administrador'?'selected':'' ?>>Administrador</option>
                                </select>
                            </td>
                            <td class="text-center table-action-cell">
                                <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                <button type="submit" name="editar_usuario" class="btn btn-sm btn-success">💾 Guardar</button>
                                <a href="Admin.php?view=usuarios" class="btn btn-sm btn-secondary">❌ Cancelar</a>
                            </td>
                        </tr>
                        </form>
                    <?php else: ?>
                        <tr>
                            <td class="col-num"><?= $i ?></td>
                            <td><?= htmlspecialchars($user['nombre']) ?></td>
                            <td><?= htmlspecialchars($user['correo']) ?></td>
                            <td><?= htmlspecialchars($user['telefono'] ?? '') ?></td>
                            <td><?= htmlspecialchars($user['tipo_usuario']) ?></td>
                            <td class="text-center table-action-cell">
                                <a href="Admin.php?view=usuarios&editar=<?= $user['id_usuario'] ?>" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                                <form method="post" class="d-inline form-eliminar-usuario">
                                    <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                    <button type="submit" name="eliminar_usuario" class="btn btn-sm btn-outline-danger">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
=======
                    <tr>
                        <td class="col-num"><?= $i ?></td>
                        <td><?= htmlspecialchars($user['nombre']) ?></td>
                        <td><?= htmlspecialchars($user['correo']) ?></td>
                        <td><?= htmlspecialchars($user['tipo_usuario']) ?></td>
                        <td><?= htmlspecialchars($user['telefono'] ?? '') ?></td>
                        <td class="text-center table-action-cell text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar-usuario"
                                data-id="<?= $user['id_usuario'] ?>"
                                data-nombre="<?= htmlspecialchars($user['nombre']) ?>"
                                data-correo="<?= htmlspecialchars($user['correo']) ?>"
                                data-tipo="<?= htmlspecialchars($user['tipo_usuario']) ?>"
                                data-telefono="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                                ✏️ Editar
                            </button>
                            <form method="post" class="d-inline form-eliminar-usuario">
                                <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                <button type="submit" name="eliminar_usuario" class="btn btn-sm btn-outline-danger">🗑️ Eliminar</button>
                            </form>
                        </td>
                    </tr>
>>>>>>> 37d623eb911e485d34ce66af60d357b7fdb58415
                <?php $i++; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-brand text-white">
                <h5 class="modal-title" id="editarUsuarioModalLabel">✏️ Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarUsuario" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="edit_id_usuario">
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_correo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="edit_correo" name="correo" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="edit_telefono" name="telefono" placeholder="+519XXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label for="edit_tipo" class="form-label">Tipo de Usuario</label>
                        <select class="form-select" id="edit_tipo" name="tipo" required>
                            <option value="Profesor">Profesor</option>
                            <option value="Encargado">Encargado</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_usuario" class="btn btn-brand">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Formulario público -->
<div class="card card-brand shadow-sm">
    <div class="card-header bg-brand text-white">Crear Cuenta</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="public-name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="tel" name="telefono" class="form-control" placeholder="+519XXXXXXXX">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contraseña</label>
                <div class="password-field">
                    <input type="password" name="contraseña" id="public-pass" class="form-control" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('public-pass')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" name="registrar_profesor_publico" class="btn btn-brand">Crear Cuenta</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="mt-4">
    <a href="<?= $esAdmin ? 'Admin.php' : '../../Public/index.php' ?>" class="btn btn-outline-brand hide-xs">🔙 Volver</a>
</div>

<?php if (!defined('EMBEDDED_VIEW')): ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../Public/js/registrar_usuario.js"></script>
<?php endif; ?>

<!-- Script de usuarios siempre se carga -->
<script src="../../Public/js/usuarios.js?v=<?= time() ?>"></script>

<?php if (!defined('EMBEDDED_VIEW')): ?>
  </main>
</body>
</html>
<?php endif; ?>
