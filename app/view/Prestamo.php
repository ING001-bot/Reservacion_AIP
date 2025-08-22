<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y tiene id_usuario
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php"); // Redirige al login si no hay sesión
    exit();
}

require '../controllers/PrestamoController.php';
require '../controllers/AulaController.php';

$prestamoController = new PrestamoController($conexion);
$aulaController = new AulaController($conexion);

$mensaje = '';
$mensaje_tipo = '';

// Tipos de equipos
$tipos = ['Laptop', 'Proyector', 'Mouse'];
$equiposPorTipo = [];
foreach ($tipos as $tipo) {
    $equiposPorTipo[$tipo] = $prestamoController->listarEquiposPorTipo($tipo);
}

// Solo aulas de tipo Regular para préstamos
$aulas = $aulaController->listarAulas('Regular');

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? date("Y-m-d");
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    $equipos = $_POST['equipos'] ?? [];
    $id_aula = $_POST['id_aula'] ?? null;

    if (!$hora_inicio) {
        $mensaje = "⚠ Debes ingresar la hora de inicio.";
        $mensaje_tipo = "error";
    } elseif (!$id_aula) {
        $mensaje = "⚠ Debes seleccionar un aula.";
        $mensaje_tipo = "error";
    } else {
        $resultado = $prestamoController->guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin, $id_aula);
        $mensaje = $resultado['mensaje'];
        $mensaje_tipo = $resultado['tipo'];
    }
}

// Obtener los préstamos del usuario
$id_usuario = $_SESSION['id_usuario'];
$prestamos = $prestamoController->listarPrestamosPorUsuario($id_usuario);
$usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');

// Fecha mínima y valor predeterminado en formato YYYY-MM-DD
$fecha_min = date('Y-m-d');
$fecha_default = $fecha_min;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Préstamo de Equipos - <?= $usuario ?></title>
<link rel="stylesheet" href="../../Public/css/estilo.css">
</head>
<body>
<main class="contenedor">
<h1>💻 Préstamo de Equipos</h1>

<?php if (!empty($mensaje)): ?>
    <p class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </p>
<?php endif; ?>

<form method="POST">
    <label for="fecha_prestamo">Fecha de Préstamo:</label>
    <input type="date" name="fecha_prestamo" id="fecha_prestamo" required min="<?= $fecha_min ?>" 
           value="<?= $fecha_default ?>">

    <?php foreach ($tipos as $tipo): ?>
        <label for="<?= $tipo ?>"><?= $tipo ?> (opcional):</label>
        <select name="equipos[<?= $tipo ?>]" id="<?= $tipo ?>">
            <option value="">-- No necesito <?= $tipo ?> --</option>
            <?php foreach ($equiposPorTipo[$tipo] as $eq): ?>
                <option value="<?= $eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endforeach; ?>

    <label for="id_aula">Aula:</label>
    <select name="id_aula" id="id_aula" required>
        <option value="">-- Selecciona un aula --</option>
        <?php foreach ($aulas as $a): ?>
            <option value="<?= $a['id_aula'] ?>"><?= htmlspecialchars($a['nombre_aula']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="hora_inicio">Hora de inicio:</label>
    <input type="time" name="hora_inicio" id="hora_inicio" required>

    <label for="hora_fin">Hora de fin (opcional):</label>
    <input type="time" name="hora_fin" id="hora_fin">

    <button type="submit">Enviar</button>
</form>

<hr>

<h2>Préstamos Registrados</h2>
<table>
<thead>
<tr>
    <th>Tipo</th>
    <th>Equipo</th>
    <th>Aula</th>
    <th>Fecha Préstamo</th>
    <th>Hora Inicio</th>
    <th>Hora Fin</th>
    <th>Fecha Devolución</th>
</tr>
</thead>
<tbody>
<?php foreach ($prestamos as $pre): ?>
<tr>
    <td><?= htmlspecialchars($pre['tipo_equipo'] ?? '-') ?></td>
    <td><?= htmlspecialchars($pre['nombre_equipo'] ?? '-') ?></td>
    <td><?= htmlspecialchars($pre['nombre_aula'] ?? '-') ?></td>
    <td><?= htmlspecialchars($pre['fecha_prestamo'] ?? '-') ?></td>
    <td><?= htmlspecialchars($pre['hora_inicio'] ?? '-') ?></td>
    <td><?= htmlspecialchars($pre['hora_fin'] ?? '-') ?></td>
    <td><?= htmlspecialchars($pre['fecha_devolucion'] ?? '-') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<a href="../view/Dashboard.php" class="btn-volver">🔙 Volver</a>
</main>
</body>
</html>
