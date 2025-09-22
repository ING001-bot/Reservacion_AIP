<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['tipo'] ?? null;
$esAdmin = ($rol === 'Administrador');

require '../controllers/UsuarioController.php';
$controller = new UsuarioController();
$data = $controller->handleRequest();

$usuarios = $data['usuarios'];
$mensaje = $data['mensaje'];
$mensaje_tipo = $data['mensaje_tipo'];
$id_editar = $_GET['editar'] ?? null; // Para edición inline
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>👤 <?= $esAdmin ? "Usuarios" : "Crear Cuenta" ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Public/css/brand.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<main class="container py-4">

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
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="admin-name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contraseña</label>
                <div class="password-field">
                    <input type="password" name="contraseña" id="admin-pass" class="form-control" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('admin-pass')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de Usuario</label>
                <select name="tipo" class="form-select" required>
                    <option value="">-- Selecciona un tipo --</option>
                    <option value="Profesor">Profesor</option>
                    <option value="Encargado">Encargado</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" name="registrar_usuario_admin" class="btn btn-brand">Registrar Usuario</button>
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
                        <th>N°</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($usuarios as $user): ?>
                    <?php if ($id_editar == $user['id_usuario']): ?>
                        <form method="post">
                        <tr>
                            <td><?= $i ?></td>
                            <td><input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" class="form-control" required></td>
                            <td><input type="email" name="correo" value="<?= htmlspecialchars($user['correo']) ?>" class="form-control" required></td>
                            <td>
                                <select name="tipo" class="form-select" required>
                                    <option value="Profesor" <?= $user['tipo_usuario']=='Profesor'?'selected':'' ?>>Profesor</option>
                                    <option value="Encargado" <?= $user['tipo_usuario']=='Encargado'?'selected':'' ?>>Encargado</option>
                                    <option value="Administrador" <?= $user['tipo_usuario']=='Administrador'?'selected':'' ?>>Administrador</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                <button type="submit" name="editar_usuario" class="btn btn-sm btn-success">💾 Guardar</button>
                                <a href="Registrar_Usuario.php" class="btn btn-sm btn-secondary">❌ Cancelar</a>
                            </td>
                        </tr>
                        </form>
                    <?php else: ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= htmlspecialchars($user['nombre']) ?></td>
                            <td><?= htmlspecialchars($user['correo']) ?></td>
                            <td><?= htmlspecialchars($user['tipo_usuario']) ?></td>
                            <td class="text-center">
                                <a href="?editar=<?= $user['id_usuario'] ?>" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                                <form method="post" class="d-inline form-eliminar-usuario">
                                    <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                    <button type="submit" name="eliminar_usuario" class="btn btn-sm btn-outline-danger">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php $i++; endforeach; ?>
                </tbody>
            </table>
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
    <a href="<?= $esAdmin ? 'Admin.php' : '../../Public/index.php' ?>" class="btn btn-outline-brand">🔙 Volver</a>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/registrar_usuario.js"></script>
<script src="../../Public/js/usuarios.js"></script>
</body>
</html>
