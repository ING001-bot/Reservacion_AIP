<?php
session_start();

// Redirigir si no está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../Public/index.php');
    exit;
}

$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
$rol = $_SESSION['tipo'] ?? 'Profesor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?= $usuario ?></title>
    <link rel="stylesheet" href="../../Public/css/dashboard.css">
</head>
<body>
    <main class="dashboard">
        <h1>Bienvenido, <?= $usuario ?></h1>
        <nav class="menu-buttons">
            <?php if ($rol === 'Profesor'): ?>
                <a href="reserva.php">📅 Reservar Aula</a>
                <a href="prestamo.php">💻 Préstamo de Equipos</a>
            <?php endif; ?>

            <?php if ($rol === 'Administrador'): ?>
                <a href="admin.php">⚙️ Administración</a>
                <a href="historial.php">📄 Historial / PDF</a>
            <?php endif; ?>

            <?php if ($rol === 'Encargado'): ?>
                <a href="historial.php">📄 Historial / PDF</a>
                <a href="devolucion.php">🔄 Registrar Devolución</a>
            <?php endif; ?>

            <?php if (in_array($rol, ['Administrador'])): ?>
                <a href="cambiar_contraseña.php">🔑 Cambiar Contraseña</a>
            <?php endif; ?>

            <a href="../controllers/LogoutController.php">Cerrar sesión</a>
        </nav>
    </main>
</body>
</html>
