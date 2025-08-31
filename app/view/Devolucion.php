<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

if (!isset($_SESSION['usuario']) || $_SESSION['tipo']!=='Encargado') {
    die("Acceso denegado");
}

if (isset($_GET['devolver'])) {
    $controller->devolverEquipo($_GET['devolver']);
}

$prestamos = $controller->obtenerTodosPrestamos();
$mensaje = $_GET['mensaje'] ?? '';
$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Devolución</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body>
    <main class="container py-4">
        <h1 class="text-center text-success mb-4">📦 Registrar Devolución</h1>
        <p class="text-center">Encargado: <strong><?= $usuario ?></strong></p>

        <?php if($mensaje): ?>
            <div class="alert alert-info text-center"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card shadow-lg mb-4">
            <div class="card-header text-white" style="background: linear-gradient(90deg,#25D366,#3a2edb);">
                <h5 class="mb-0 text-center">Todos los Préstamos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-primary">        
                            <tr>
                                <th>Equipo</th>
                                <th>Responsable</th>
                                <th>Aula</th>
                                <th>Tipo Aula</th>
                                <th>Fecha Préstamo</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(!empty($prestamos)): ?>
                            <?php foreach($prestamos as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nombre_equipo']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_aula']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo']) ?></td>
                                    <td><?= htmlspecialchars($row['fecha_prestamo']) ?></td>
                                    <td><?= htmlspecialchars($row['hora_inicio']) ?></td>
                                    <td><?= htmlspecialchars($row['hora_fin']) ?></td>
                                    <td><?= $row['fecha_devolucion'] ? htmlspecialchars($row['fecha_devolucion']) : '---' ?></td>
                                    <td>
                                        <?php if($row['estado']==='Prestado'): ?>
                                            <span class="badge bg-warning text-dark">Prestado</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Devuelto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['estado']==='Prestado'): ?>
                                            <a href="?devolver=<?= $row['id_prestamo'] ?>" 
                                               class="btn btn-sm btn-outline-success">Devolver</a>
                                        <?php else: ?>
                                            <span class="text-success fw-bold">✔</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No hay préstamos registrados.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-outline-primary">⬅ Volver al Dashboard</a>
        </div>
    </main>
</body>
</html>
