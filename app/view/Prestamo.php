<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y tiene id_usuario
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php"); 
    exit();
}

require_once '../controllers/PrestamoController.php';
require_once '../controllers/AulaController.php';

$prestamoController = new PrestamoController($conexion);
$aulaController = new AulaController($conexion);

$mensaje = '';
$mensaje_tipo = '';

// Solo aulas de tipo Regular para préstamos
$aulas = $aulaController->listarAulas('Regular');

// Cargar inventario por tipo (activos y no prestados hoy)
$laptops = $prestamoController->listarEquiposPorTipo('Laptop');
$proyectores = $prestamoController->listarEquiposPorTipo('Proyector');
$mouses = $prestamoController->listarEquiposPorTipo('Mouse');
$extensiones = $prestamoController->listarEquiposPorTipo('Extensión');

// Procesar formulario (selección por equipo específico)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d');
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;
    $id_aula = $_POST['id_aula'] ?? null;

    // IDs seleccionados
    $id_laptop = (int)($_POST['id_laptop'] ?? 0);
    $id_proyector = (int)($_POST['id_proyector'] ?? 0);
    $use_mouse = isset($_POST['use_mouse']);
    $id_mouse = $use_mouse ? (int)($_POST['id_mouse'] ?? 0) : 0;
    $use_extension = isset($_POST['use_extension']);
    $id_extension = $use_extension ? (int)($_POST['id_extension'] ?? 0) : 0;

    $equipos = array_values(array_filter([$id_laptop, $id_proyector, $id_mouse, $id_extension]));

    if (!$hora_inicio) {
        $mensaje = '⚠ Debes ingresar la hora de inicio.';
        $mensaje_tipo = 'danger';
    } elseif (!$id_aula) {
        $mensaje = '⚠ Debes seleccionar un aula.';
        $mensaje_tipo = 'danger';
    } elseif (empty($equipos)) {
        $mensaje = '⚠ Debes seleccionar al menos un equipo.';
        $mensaje_tipo = 'danger';
    } else {
        $resultado = $prestamoController->guardarPrestamosMultiple(
            (int)$id_usuario,
            $equipos,
            $fecha_prestamo,
            $hora_inicio,
            $hora_fin ?: null,
            (int)$id_aula
        );
        $mensaje = $resultado['mensaje'] ?? '';
        $mensaje_tipo = ($resultado['tipo'] ?? '') === 'error' ? 'danger' : 'success';
    }
}

// Obtener packs del usuario
$id_usuario = $_SESSION['id_usuario'];
$packs = $prestamoController->listarPacksPorUsuario((int)$id_usuario);
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
<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container py-4">
    <h1 class="text-center text-brand mb-4">💻 Préstamo de Equipos</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $mensaje_tipo ?> text-center shadow-sm">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario (Pack) -->
    <div class="card card-brand shadow-lg mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
                    <input type="date" name="fecha_prestamo" id="fecha_prestamo" 
                           class="form-control" required min="<?= $fecha_min ?>" 
                           value="<?= $fecha_default ?>">
                </div>

                <div class="col-12">
                    <h5 class="mt-2">Selecciona equipos base</h5>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Laptop</label>
                    <select class="form-select" name="id_laptop" id="id_laptop">
                        <option value="0">Seleccionar...</option>
                        <?php foreach ($laptops as $eq): ?>
                            <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proyector</label>
                    <select class="form-select" name="id_proyector" id="id_proyector">
                        <option value="0">Seleccionar...</option>
                        <?php foreach ($proyectores as $eq): ?>
                            <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <h5 class="mt-3">Complementos</h5>
                </div>
                <!-- Mouse (aparece cuando se elige una Laptop) -->
                <div class="col-md-4" id="wrap_mouse" style="display:none">
                    <label class="form-label">Mouse (opcional)</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_mouse" name="use_mouse" value="1">
                        </span>
                        <select class="form-select" name="id_mouse" id="id_mouse" disabled>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($mouses as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Extensión (aparece cuando se elige un Proyector) -->
                <div class="col-md-4" id="wrap_extension" style="display:none">
                    <label class="form-label">Extensión (opcional)</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_extension" name="use_extension" value="1">
                        </span>
                        <select class="form-select" name="id_extension" id="id_extension" disabled>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($extensiones as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

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
                    <label for="hora_fin" class="form-label">Hora de fin</label>
                    <input type="time" name="hora_fin" id="hora_fin" class="form-control">
                </div>

                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-brand px-4">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de préstamos (packs) -->
    <h2 class="text-center text-brand mb-3">📖 Préstamos Registrados</h2>
    <div class="table-responsive shadow-lg">
        <table class="table table-hover align-middle text-center table-brand">
            <thead class="table-primary text-center">
                <tr>
                    <th>Detalle</th>
                    <th>Aula</th>
                    <th>Fecha Préstamo</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                    <th>Fecha Devolución</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packs as $p): ?>
                    <?php $items = $prestamoController->obtenerItemsDePack((int)$p['id_pack']); ?>
                    <tr>
                        <td>
                            <?php if ($items): ?>
                                <?php foreach ($items as $it): ?>
                                    <span class="badge bg-secondary me-1">
                                        <?= htmlspecialchars($it['tipo_equipo']) ?> x<?= (int)$it['cantidad'] ?><?= $it['es_complemento'] ? ' (C)' : '' ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['nombre_aula'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['fecha_prestamo'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['hora_inicio'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['hora_fin'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['fecha_devolucion'] ?? '-') ?></td>
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
<script src="../../Public/js/theme.js"></script>
<script>
    (function(){
        const selLaptop = document.getElementById('id_laptop');
        const selProy = document.getElementById('id_proyector');
        const wrapMouse = document.getElementById('wrap_mouse');
        const wrapExt = document.getElementById('wrap_extension');
        const useMouse = document.getElementById('use_mouse');
        const useExt = document.getElementById('use_extension');
        const selMouse = document.getElementById('id_mouse');
        const selExt = document.getElementById('id_extension');

        function refreshComplements(){
            const hasLaptop = parseInt(selLaptop.value||'0',10) > 0;
            wrapMouse.style.display = hasLaptop ? '' : 'none';
            if (!hasLaptop) { useMouse.checked = false; selMouse.disabled = true; selMouse.value = '0'; }
            const hasProy = parseInt(selProy.value||'0',10) > 0;
            wrapExt.style.display = hasProy ? '' : 'none';
            if (!hasProy) { useExt.checked = false; selExt.disabled = true; selExt.value = '0'; }
        }
        function toggleSelect(chk, sel){ sel.disabled = !chk.checked; if (!chk.checked) sel.value = '0'; }

        selLaptop.addEventListener('change', refreshComplements);
        selProy.addEventListener('change', refreshComplements);
        if (useMouse) useMouse.addEventListener('change', ()=>toggleSelect(useMouse, selMouse));
        if (useExt) useExt.addEventListener('change', ()=>toggleSelect(useExt, selExt));
        refreshComplements();
    })();
</script>
</body>
</html>
