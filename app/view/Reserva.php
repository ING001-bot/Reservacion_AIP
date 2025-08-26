<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../controllers/ReservaController.php';

$nombreProfesor = $_SESSION['usuario'] ?? 'Invitado';

// Fecha mínima y valor predeterminado en formato YYYY-MM-DD
$fecha_min = date('Y-m-d'); // hoy
$fecha_default = $fecha_min;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservar Aula</title>
<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Estilos de marca -->
<link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="text-center text-brand mb-4">📅 Reservar Aula</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info text-center shadow-sm">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card shadow-lg mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Profesor</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($nombreProfesor) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Seleccionar Aula (Solo AIP)</label>
                    <select name="id_aula" class="form-select" required>
                        <?php foreach ($aulas as $aula): ?>
                            <option value="<?= $aula['id_aula'] ?>">
                                <?= $aula['nombre_aula'] ?> (Cap: <?= $aula['capacidad'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" required 
                           min="<?= $fecha_min ?>" value="<?= $fecha_default ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Hora Inicio</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Hora Fin</label>
                    <input type="time" name="hora_fin" class="form-control" required>
                </div>

                <div class="col-12 text-center">
                    <button type="submit" name="accion" value="guardar" class="btn btn-brand px-4">Reservar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de reservas -->
    <h2 class="text-center text-brand mb-3">📖 Reservas Registradas</h2>
    <div class="table-responsive shadow-lg">
        <table class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>Profesor</th>
                    <th>Aula</th>
                    <th>Capacidad</th>
                    <th>Fecha</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <tr>
                        <td><?= htmlspecialchars($reserva['profesor']) ?></td>
                        <td><?= htmlspecialchars($reserva['nombre_aula']) ?></td>
                        <td><?= htmlspecialchars($reserva['capacidad']) ?></td>
                        <td><?= htmlspecialchars($reserva['fecha']) ?></td>
                        <td><?= htmlspecialchars($reserva['hora_inicio']) ?></td>
                        <td><?= htmlspecialchars($reserva['hora_fin']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center mt-3">
        <a href="dashboard.php" class="btn btn-outline-brand">⬅ Volver al Dashboard</a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
