<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['tipo'] ?? null;
if ($rol !== 'Administrador') {
    header('Location: Dashboard.php'); // Solo admins pueden entrar
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙ Administración</title>
    <link rel="stylesheet" href="../../Public/css/admin.css">
</head>
<body>
<main class="dashboard">
    <h1>⚙ Administración</h1>

    <nav class="menu-buttons">
        <a href="registrar_usuario.php">👤 Registrar Usuario</a>
        <a href="registrar_equipo.php">💻 Registrar Equipo</a>
        <a href="registrar_aula.php">🏫 Registrar Aula</a>
        <a href="Dashboard.php" class="logout">🔙 Volver</a>
    </nav>
</main>
</body>
</html>
