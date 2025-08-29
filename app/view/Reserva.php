<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../controllers/ReservaController.php';

$nombreProfesor = $_SESSION['usuario'] ?? 'Invitado';

// Fecha mínima y valor predeterminado en formato YYYY-MM-DD
$fecha_min = date('Y-m-d'); // hoy
// Mantener la fecha seleccionada tras autosubmit
$fecha_default = $_POST['fecha'] ?? $fecha_min;
// Mantener el aula seleccionada tras autosubmit
$id_aula_selected = $_POST['id_aula'] ?? (isset($aulas[0]['id_aula']) ? $aulas[0]['id_aula'] : null);

// Si tenemos selección de fecha y aula, preparar reservas del cuadro
$fecha_para_cuadro = $fecha_default;
$id_aula_para_cuadro = $id_aula_selected;
$reservas_existentes = [];
if (!empty($fecha_para_cuadro) && !empty($id_aula_para_cuadro)) {
    $reservas_existentes = $controller->obtenerReservasPorFecha($id_aula_para_cuadro, $fecha_para_cuadro);
}
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
<style>
    /* Opcional: hacer que los chips ocupen el ancho disponible elegantemente */
    #cuadro-horas .btn { min-width: 110px; }
</style>
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="text-center text-brand mb-4">📅 Reservar Aula</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info text-center shadow-sm">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Formulario -->
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Profesor</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($nombreProfesor) ?>" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Seleccionar Aula (Solo AIP)</label>
                            <select name="id_aula" class="form-select" required onchange="this.form.submit()">
                                <?php foreach ($aulas as $aula): ?>
                                    <option value="<?= $aula['id_aula'] ?>"
                                        <?= ($id_aula_selected == $aula['id_aula']) ? 'selected' : '' ?>>
                                        <?= $aula['nombre_aula'] ?> (Cap: <?= $aula['capacidad'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" required 
                                   min="<?= $fecha_min ?>" value="<?= htmlspecialchars($fecha_default) ?>"
                                   onchange="this.form.submit()">
                        </div>

                        <div class="col-6">
                            <label class="form-label">Hora Inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" required>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Hora Fin</label>
                            <input type="time" name="hora_fin" class="form-control" required>
                        </div>

                        <div class="col-12 text-center mt-2">
                            <button type="submit" name="accion" value="guardar" class="btn btn-brand px-4">Reservar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cuadro de horas -->
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Disponibilidad de Horas</label>
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            <?= htmlspecialchars($fecha_para_cuadro) ?>
                        </span>
                    </div>

                    <div id="cuadro-horas" class="d-flex flex-wrap gap-2">
                        <?php
                        if (!empty($fecha_para_cuadro) && !empty($id_aula_para_cuadro)) {
                            // Configuración de rango y bloque
                            $t_inicio = strtotime("06:00");
                            $t_fin    = strtotime("18:35");
                            $intervalo = 45 * 60; // 30 min (cambiar a 45*60 si deseas 45 min)

                            while ($t_inicio < $t_fin) {
                                $inicio_hm = date("H:i", $t_inicio);
                                $fin_hm    = date("H:i", $t_inicio + $intervalo);
                                // Normalizar a HH:MM:SS para comparar con lo que viene de BD
                                $inicio = $inicio_hm . ":00";
                                $fin    = $fin_hm . ":00";

                                $ocupada = false;
                                foreach ($reservas_existentes as $res) {
                                    // $res['hora_inicio'] y $res['hora_fin'] están en HH:MM:SS
                                    // choque si: inicio < res.fin  y  fin > res.inicio
                                    if ($inicio < $res['hora_fin'] && $fin > $res['hora_inicio']) {
                                        $ocupada = true;
                                        break;
                                    }
                                }

                                $clase = $ocupada ? "btn btn-danger btn-sm" : "btn btn-success btn-sm";
                                echo "<button type='button' class='{$clase} mb-1'>{$inicio_hm} - {$fin_hm}</button>";

                                $t_inicio += $intervalo;
                            }
                        } else {
                            echo "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
                        }
                        ?>
                    </div>

                    <div class="mt-3">
                        <span class="badge bg-success">Disponible</span>
                        <span class="badge bg-danger ms-2">Ocupada</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reservas -->
    <h2 class="text-center text-brand my-4">📖 Reservas Registradas</h2>
    <div class="table-responsive shadow-lg">
        <table class="table table-hover align-middle">
            <thead class="table-primary text-center">
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
