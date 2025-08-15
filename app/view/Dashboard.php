<?php
session_start();

if (empty($_SESSION['usuario'])) {
    header('Location: ../../Public/index.php');
    exit;
}

function esAdministrador(): bool {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador';
}

$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
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
            <a href="reservar.php">📅 Reservar Aula</a>
            <a href="prestamo.php">💻 Préstamo de Equipos</a>
            <a href="devolucion.php">🔄 Registrar Devolución</a>
            <a href="historial.php">📄 Historial / PDF</a>
            <a href="cambiar_contraseña.php">🔑 Cambiar Contraseña</a>
            <?php if (esAdministrador()) : ?>
                <a href="admin.php">⚙️ Administración</a>

            <?php endif; ?>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </main>
</body>
</html>
