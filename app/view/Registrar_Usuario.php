<?php
session_start();
$rol = $_SESSION['tipo'] ?? null;
$esAdmin = ($rol === 'Administrador');

require '../controllers/UsuarioController.php';
$controller = new UsuarioController();
$data = $controller->handleRequest();

$usuarios = $data['usuarios'];
$mensaje = $data['mensaje'];
$mensaje_tipo = $data['mensaje_tipo'];
$id_editar = $_GET['editar'] ?? null; // Para editar inline
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👤 <?= $esAdmin ? "Usuarios" : "Crear Cuenta" ?></title>
    <link rel="stylesheet" href="../../Public/css/usuario.css">
</head>
<body>
<main class="contenedor">
<h1>👤 <?= $esAdmin ? "Gestión de Usuarios" : "Crear Cuenta" ?></h1>

<?php if (!empty($mensaje)): ?>
    <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<?php if ($esAdmin): ?>
<div class="tarjeta">
    <form method="post">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
        <label>Correo:</label>
        <input type="email" name="correo" required>
        <label>Contraseña:</label>
        <input type="password" name="contraseña" required minlength="6">
        <label>Tipo de Usuario:</label>
        <select name="tipo" required>
            <option value="">-- Selecciona un tipo --</option>
            <option value="Profesor">Profesor</option>
            <option value="Encargado">Encargado</option>
            <option value="Administrador">Administrador</option>
        </select>
        <button type="submit" name="registrar_usuario_admin">Registrar Usuario</button>
    </form>
</div>

<div class="tabla-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Tipo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $user): ?>
            <?php if ($id_editar == $user['id_usuario']): ?>
                <form method="post">
                    <tr>
                        <td><?= $user['id_usuario'] ?></td>
                        <td><input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required></td>
                        <td><input type="email" name="correo" value="<?= htmlspecialchars($user['correo']) ?>" required></td>
                        <td>
                            <select name="tipo" required>
                                <option value="Profesor" <?= $user['tipo_usuario']=='Profesor'?'selected':'' ?>>Profesor</option>
                                <option value="Encargado" <?= $user['tipo_usuario']=='Encargado'?'selected':'' ?>>Encargado</option>
                                <option value="Administrador" <?= $user['tipo_usuario']=='Administrador'?'selected':'' ?>>Administrador</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                            <button type="submit" name="editar_usuario">💾 Guardar</button>
                            <a href="Registrar_Usuario.php">❌ Cancelar</a>
                        </td>
                    </tr>
                </form>
            <?php else: ?>
                <tr>
                    <td><?= $user['id_usuario'] ?></td>
                    <td><?= htmlspecialchars($user['nombre']) ?></td>
                    <td><?= htmlspecialchars($user['correo']) ?></td>
                    <td><?= htmlspecialchars($user['tipo_usuario']) ?></td>
                    <td>
                        <a href="?editar=<?= $user['id_usuario'] ?>">✏️ Editar</a> |
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                            <button type="submit" name="eliminar_usuario"  onclick="return confirm('¿Seguro que deseas eliminar esta aula?')">🗑️ Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="tarjeta">
    <form method="post">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
        <label>Correo:</label>
        <input type="email" name="correo" required>
        <label>Contraseña:</label>
        <input type="password" name="contraseña" required minlength="6">
        <button type="submit" name="registrar_profesor_publico">Crear Cuenta</button>
    </form>
</div>
<?php endif; ?>

<a href="<?= $esAdmin ? 'Admin.php' : '../../Public/index.php' ?>" class="volver">🔙 Volver</a>
</main>
</body>
</html>
