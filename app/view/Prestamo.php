<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y tiene id_usuario
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php"); 
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
        $mensaje_tipo = "danger";
    } elseif (!$id_aula) {
        $mensaje = "⚠ Debes seleccionar un aula.";
        $mensaje_tipo = "danger";
    } else {
        $resultado = $prestamoController->guardarPrestamosMultiple(
            $id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin, $id_aula
        );
        $mensaje = $resultado['mensaje'];
        $mensaje_tipo = $resultado['tipo'] === "error" ? "danger" : "success";
    }
}

// Obtener los préstamos del usuario
$id_usuario = $_SESSION['id_usuario'];
$prestamos = $prestamoController->listarPrestamosPorUsuario($id_usuario);
$usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');

$fecha_min = date('Y-m-d');
$fecha_default = $fecha_min;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Préstamo de Equipos - <?= $usuario ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="text-center text-brand mb-4">💻 Préstamo de Equipos</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $mensaje_tipo ?> text-center shadow-sm">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card shadow-lg mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
                    <input type="date" name="fecha_prestamo" id="fecha_prestamo" 
                           class="form-control" required min="<?= $fecha_min ?>" 
                           value="<?= $fecha_default ?>">
                </div>

                <?php foreach ($tipos as $tipo): ?>
                    <div class="col-md-4">
                        <label for="<?= $tipo ?>" class="form-label"><?= $tipo ?> (opcional)</label>
                        <select name="equipos[<?= $tipo ?>]" id="<?= $tipo ?>" class="form-select">
                            <option value="">-- No necesito <?= $tipo ?> --</option>
                            <?php foreach ($equiposPorTipo[$tipo] as $eq): ?>
                                <option value="<?= $eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>

                <div class="col-md-6">
                    <label for="id_aula" class="form-label">Aula</label>
                    <select name="id_aula" id="id_aula" class="form-select" required>
                        <option value="">-- Selecciona un aula --</option>
                        <?php foreach ($aulas as $a): ?>
                            <option value="<?= $a['id_aula'] ?>"><?= htmlspecialchars($a['nombre_aula']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="hora_inicio" class="form-label">Hora de inicio</label>
                    <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label for="hora_fin" class="form-label">Hora de fin (opcional)</label>
                    <input type="time" name="hora_fin" id="hora_fin" class="form-control">
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-brand px-4">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de préstamos -->
    <h2 class="text-center text-brand mb-3">📖 Préstamos Registrados</h2>
    <div class="table-responsive shadow-lg">
        <table class="table table-hover align-middle">
            <thead class="table-primary text-center">
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
    </div>
                    
    <div class="text-center mt-3">
        <a href="../view/Dashboard.php" class="btn btn-outline-brand">🔙 Volver</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
