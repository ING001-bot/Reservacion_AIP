<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

$mensaje = '';
$mensaje_tipo = '';

$tipos = ['Laptop', 'Proyector', 'Mouse'];
$equiposPorTipo = [];

foreach ($tipos as $tipo) {
    $equiposPorTipo[$tipo] = $controller->listarEquiposPorTipo($tipo);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipos'])) {
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    $equipos = $_POST['equipos'] ?? [];

    if ($hora_inicio) {
        $resultado = $controller->guardarPrestamosMultiple($id_usuario, $equipos, $hora_inicio, $hora_fin);
        $mensaje = $resultado['mensaje'];
        $mensaje_tipo = $resultado['tipo'];
    } else {
        $mensaje = "⚠ Debes ingresar la hora de inicio.";
        $mensaje_tipo = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Préstamo de Equipos</title>
    <link rel="stylesheet" href="../../Public/css/estilo.css">
</head>
<body>
    <div class="formulario">
        <h2> Prestamo de Equipo</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($tipos as $tipo): ?>
                <label for="<?= $tipo ?>"><?= $tipo ?> (opcional):</label>
                <select name="equipos[<?= $tipo ?>]" id="<?= $tipo ?>">
                    <option value="">-- No necesito <?= $tipo ?> --</option>
                    <?php foreach ($equiposPorTipo[$tipo] as $eq): ?>
                        <option value="<?= $eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                    <?php endforeach; ?>
                </select>
                <br>
            <?php endforeach; ?>

            <label for="hora_inicio">Hora de inicio:</label>
            <input type="time" name="hora_inicio" id="hora_inicio" required>

            <label for="hora_fin">Hora de fin (opcional):</label>
            <input type="time" name="hora_fin" id="hora_fin">

            <button type="submit">Enviar</button>
        </form>

        <a href="../view/Dashboard.php" class="btn">🔙 Volver</a>
    </div>
</body>
</html>
