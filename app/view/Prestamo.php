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

// Solo aulas de tipo REGULAR para préstamos (no AIP)
$aulas = $aulaController->listarAulas('REGULAR');

// Cargar inventario por tipo con stock disponible (activos y disponibles para la fecha)
$fecha_prestamo_check = $_POST['fecha_prestamo'] ?? date('Y-m-d', strtotime('+1 day'));
$laptops = $prestamoController->listarEquiposPorTipoConStock('LAPTOP', $fecha_prestamo_check);
$proyectores = $prestamoController->listarEquiposPorTipoConStock('PROYECTOR', $fecha_prestamo_check);
$mouses = $prestamoController->listarEquiposPorTipoConStock('MOUSE', $fecha_prestamo_check);
$extensiones = $prestamoController->listarEquiposPorTipoConStock('EXTENSIÓN', $fecha_prestamo_check);
$parlantes = $prestamoController->listarEquiposPorTipoConStock('PARLANTE', $fecha_prestamo_check);

// Calcular totales disponibles
$total_laptops = array_sum(array_column($laptops, 'disponible'));
$total_proyectores = array_sum(array_column($proyectores, 'disponible'));
$total_mouses = array_sum(array_column($mouses, 'disponible'));
$total_extensiones = array_sum(array_column($extensiones, 'disponible'));
$total_parlantes = array_sum(array_column($parlantes, 'disponible'));

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
    $use_parlante = isset($_POST['use_parlante']);
    $id_parlante = $use_parlante ? (int)($_POST['id_parlante'] ?? 0) : 0;

    $equipos = array_values(array_filter([$id_laptop, $id_proyector, $id_mouse, $id_extension, $id_parlante]));

    if (!$hora_inicio) {
        $mensaje = '⚠ Debes ingresar la hora de inicio.';
        $mensaje_tipo = 'danger';
    } elseif (!$id_aula || $id_aula === '') {
        $mensaje = '⚠ Debes seleccionar un aula.';
        $mensaje_tipo = 'danger';
    } elseif (empty($equipos)) {
        $mensaje = '⚠ Debes seleccionar al menos un equipo.';
        $mensaje_tipo = 'danger';
    } else {
        // Validar que el aula existe
        $id_aula = (int)$id_aula;
        if ($id_aula <= 0) {
            $mensaje = '⚠ ID de aula inválido.';
            $mensaje_tipo = 'danger';
        } else {
            $resultado = $prestamoController->guardarPrestamosMultiple(
                (int)$id_usuario,
                $equipos,
                $fecha_prestamo,
                $hora_inicio,
                $id_aula,
                $hora_fin ?: null
            );
            $mensaje = $resultado['mensaje'] ?? '';
            $mensaje_tipo = ($resultado['tipo'] ?? '') === 'error' ? 'danger' : 'success';
        }
    }
}

// Obtener préstamos del usuario (ambos sistemas)
$id_usuario = $_SESSION['id_usuario'];
$packs = $prestamoController->listarPacksPorUsuario((int)$id_usuario);
$prestamosIndividuales = $prestamoController->listarPrestamosPorUsuario((int)$id_usuario);
$usuario = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');

date_default_timezone_set('America/Lima');
$hoy = new DateTime('today');
$mañana = (clone $hoy)->modify('+1 day');
$fecha_min = $mañana->format('Y-m-d');
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

    <!-- DEBUG: Verificar stock y aulas -->
    <?php if (empty($aulas)): ?>
        <div class="alert alert-danger">
            <strong>❌ No hay aulas REGULAR disponibles.</strong> 
            <p class="mb-0">Contacta con el administrador para que cree al menos un aula de tipo REGULAR.</p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($laptops) && empty($proyectores) && empty($mouses) && empty($extensiones) && empty($parlantes)): ?>
        <div class="alert alert-warning">
            <strong>⚠️ No hay equipos disponibles.</strong> 
            <p class="mb-0">Verifica que:</p>
            <ul class="mb-0">
                <li>Hayas registrado equipos en el sistema</li>
                <li>Los tipos de equipos sean: <strong>LAPTOP, PROYECTOR, MOUSE, EXTENSIÓN, PARLANTE</strong> (en mayúsculas)</li>
                <li>Los equipos estén marcados como <strong>activos</strong></li>
            </ul>
            <a href="Admin.php?view=equipos" class="btn btn-sm btn-primary mt-2">Ir a Gestión de Equipos</a>
        </div>
    <?php endif; ?>
    
    <!-- Formulario (Pack) -->
    <div class="card card-brand shadow-lg mb-4">
        <div class="card-body">
            <div class="mb-2 text-uppercase small text-muted fw-semibold">Paso 1 · Selección rápida</div>
            
            <!-- Indicadores de stock disponible -->
            <div class="alert alert-info mb-3">
                <strong>📊 Stock Disponible:</strong>
                <span class="badge bg-primary ms-2">💻 Laptops: <?= $total_laptops ?></span>
                <span class="badge bg-primary ms-2">📽 Proyectores: <?= $total_proyectores ?></span>
                <span class="badge bg-primary ms-2">🔌 Extensiones: <?= $total_extensiones ?></span>
                <span class="badge bg-secondary ms-2">🖱 Mouses: <?= $total_mouses ?></span>
                <span class="badge bg-secondary ms-2">🔊 Parlantes: <?= $total_parlantes ?></span>
            </div>
            
            <div class="d-flex flex-wrap gap-2 mb-3 filters-actions">
                <?php $hasLap = $total_laptops>0; $hasProy = $total_proyectores>0; $hasExt = $total_extensiones>0; $hasParl = $total_parlantes>0; ?>
                <button type="button" class="btn btn-brand btn-control" id="pack-completo" <?= ($hasLap && $hasProy && $hasExt)?'':'disabled' ?>>📦 Laptop + Proyector + Extensión</button>
                <button type="button" class="btn btn-outline-brand btn-control" id="pack-proyector" <?= ($hasProy && $hasExt)?'':'disabled' ?>>📽 Solo Proyector + Extensión</button>
                <button type="button" class="btn btn-outline-brand btn-control" id="pack-laptop" <?= $hasLap?'':'disabled' ?>>💻 Solo Laptop</button>
                <button type="button" class="btn btn-outline-secondary btn-control" id="pack-parlante" <?= $hasParl?'':'disabled' ?>>🔊 Solo Parlante</button>
                <button type="button" class="btn btn-outline-danger btn-control" id="pack-limpiar">✖ Limpiar</button>
            </div>
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
                    <input type="date" name="fecha_prestamo" id="fecha_prestamo" 
                           class="form-control" required min="<?= $fecha_min ?>" 
                           value="<?= $fecha_default ?>">
                </div>

                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                        <h5 class="m-0">Selecciona equipos base</h5>
                        <div id="selection-summary" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Laptop <small class="text-muted">(<?= count($laptops) ?> disp.)</small></label>
                    <select class="form-select" name="id_laptop" id="id_laptop" <?= count($laptops)==0?'disabled':'' ?>>
                        <option value="0">Seleccionar...</option>
                        <?php foreach ($laptops as $eq): ?>
                            <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($laptops)==0): ?><div class="form-text text-danger">Sin stock disponible de Laptops</div><?php endif; ?>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Proyector <small class="text-muted">(<?= count($proyectores) ?> disp.)</small></label>
                    <select class="form-select" name="id_proyector" id="id_proyector" <?= count($proyectores)==0?'disabled':'' ?>>
                        <option value="0">Seleccionar...</option>
                        <?php foreach ($proyectores as $eq): ?>
                            <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($proyectores)==0): ?><div class="form-text text-danger">Sin stock disponible de Proyectores</div><?php endif; ?>
                </div>

                <div class="col-12">
                    <h5 class="mt-3">Complementos</h5>
                </div>
                <!-- Mouse (aparece cuando se elige una Laptop) -->
                <div class="col-md-4 col-12" id="wrap_mouse" style="display:none">
                    <label class="form-label">Mouse (opcional) <small class="text-muted">(<?= count($mouses) ?> disp.)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_mouse" name="use_mouse" value="1">
                        </span>
                        <select class="form-select" name="id_mouse" id="id_mouse" disabled <?= count($mouses)==0?'data-empty="1"':'' ?>>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($mouses as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (count($mouses)==0): ?><div class="form-text text-muted">No hay Mouse en stock.</div><?php endif; ?>
                </div>
                <!-- Extensión (aparece cuando se elige un Proyector) -->
                <div class="col-md-4 col-12" id="wrap_extension" style="display:none">
                    <label class="form-label">Extensión (opcional) <small class="text-muted">(<?= count($extensiones) ?> disp.)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_extension" name="use_extension" value="1">
                        </span>
                        <select class="form-select" name="id_extension" id="id_extension" disabled <?= count($extensiones)==0?'data-empty="1"':'' ?>>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($extensiones as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (count($extensiones)==0): ?><div class="form-text text-muted">No hay Extensiones en stock.</div><?php endif; ?>
                </div>
                <!-- Parlante (aparece siempre como opcional) -->
                <div class="col-md-4 col-12" id="wrap_parlante">
                    <label class="form-label">Parlante (opcional) <small class="text-muted">(<?= count($parlantes) ?> disp.)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <input class="form-check-input mt-0" type="checkbox" id="use_parlante" name="use_parlante" value="1">
                        </span>
                        <select class="form-select" name="id_parlante" id="id_parlante" disabled <?= count($parlantes)==0?'data-empty="1"':'' ?>>
                            <option value="0">Seleccionar...</option>
                            <?php foreach ($parlantes as $eq): ?>
                                <option value="<?= (int)$eq['id_equipo'] ?>"><?= htmlspecialchars($eq['nombre_equipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (count($parlantes)==0): ?><div class="form-text text-muted">No hay Parlantes en stock.</div><?php endif; ?>
                </div>

                <div class="col-12 mt-2">
                    <div class="text-uppercase small text-muted fw-semibold">Paso 2 · Aula y horario</div>
                </div>
                <div class="col-md-6">
                    <label for="id_aula" class="form-label">Aula</label>
                    <select name="id_aula" id="id_aula" class="form-select" required <?= empty($aulas)?'disabled':'' ?>>
                        <option value="">-- Selecciona un aula --</option>
                        <?php foreach ($aulas as $a): ?>
                            <option value="<?= (int)$a['id_aula'] ?>"><?= htmlspecialchars($a['nombre_aula']) ?></option>
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

                <div class="col-12 mt-2">
                    <div class="text-uppercase small text-muted fw-semibold">Paso 3 · Confirmar</div>
                </div>
                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-brand px-4" <?= empty($aulas)?'disabled':'' ?>>Enviar</button>
                    <?php if (empty($aulas)): ?>
                        <div class="text-danger mt-2">Debes crear al menos un aula antes de registrar préstamos.</div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de préstamos -->
    <h2 class="text-center text-brand mb-3">📖 Mis Préstamos Registrados</h2>
    <div class="table-responsive shadow-lg">
        <table class="table table-hover align-middle text-center table-brand">
            <thead class="table-primary text-center">
                <tr>
                    <th>Equipo(s)</th>
                    <th>Aula</th>
                    <th>Fecha</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                    <th>Estado</th>
                    <th>Devolución</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamosIndividuales) && empty($packs)): ?>
                    <tr>
                        <td colspan="7" class="text-muted py-4">
                            No tienes préstamos registrados aún.
                        </td>
                    </tr>
                <?php endif; ?>
                
                <!-- Préstamos individuales (sistema antiguo) -->
                <?php 
                $prestamosAgrupados = [];
                foreach ($prestamosIndividuales as $p) {
                    $key = $p['fecha_prestamo'] . '|' . $p['hora_inicio'] . '|' . ($p['id_aula'] ?? 0);
                    if (!isset($prestamosAgrupados[$key])) {
                        $prestamosAgrupados[$key] = [
                            'equipos' => [],
                            'aula' => $p['nombre_aula'] ?? '-',
                            'fecha' => $p['fecha_prestamo'],
                            'hora_inicio' => $p['hora_inicio'],
                            'hora_fin' => $p['hora_fin'] ?? '-',
                            'estado' => $p['estado'],
                            'fecha_devolucion' => $p['fecha_devolucion'] ?? '-'
                        ];
                    }
                    $prestamosAgrupados[$key]['equipos'][] = $p['nombre_equipo'] ?? 'Equipo';
                }
                
                foreach ($prestamosAgrupados as $grupo): ?>
                    <tr>
                        <td>
                            <?php foreach ($grupo['equipos'] as $eq): ?>
                                <span class="badge bg-info me-1"><?= htmlspecialchars($eq) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= htmlspecialchars($grupo['aula']) ?></td>
                        <td><?= htmlspecialchars($grupo['fecha']) ?></td>
                        <td><?= htmlspecialchars($grupo['hora_inicio']) ?></td>
                        <td><?= htmlspecialchars($grupo['hora_fin']) ?></td>
                        <td>
                            <?php if ($grupo['estado'] === 'Prestado'): ?>
                                <span class="badge bg-warning">Prestado</span>
                            <?php else: ?>
                                <span class="badge bg-success">Devuelto</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($grupo['fecha_devolucion']) ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <!-- Packs (sistema nuevo) -->
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
                        <td>
                            <?php if ($p['estado'] === 'Prestado'): ?>
                                <span class="badge bg-warning">Prestado</span>
                            <?php else: ?>
                                <span class="badge bg-success">Devuelto</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['fecha_devolucion'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
                    
    <div class="text-center mt-3">
        <a href="../view/Dashboard.php" class="btn btn-outline-brand hide-xs">🔙 Volver</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/theme.js"></script>
<script>
    (function(){
        // Validación de fecha antes de enviar el formulario
        const form = document.querySelector('form[method="POST"]');
        const fechaInput = document.getElementById('fecha_prestamo');
        
        if (form && fechaInput) {
            form.addEventListener('submit', function(e) {
                const fechaSeleccionada = fechaInput.value;
                if (!fechaSeleccionada) return;
                
                // Validar que la fecha sea al menos 1 día después de hoy
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                const mañana = new Date(hoy);
                mañana.setDate(mañana.getDate() + 1);
                const fecha = new Date(fechaSeleccionada + 'T00:00:00');
                
                if (fecha < mañana) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: '⚠️ Fecha no permitida',
                        text: 'Solo puedes solicitar préstamos a partir del día siguiente. Los préstamos deben hacerse con anticipación, no el mismo día.',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
            });
        }
        const selLaptop = document.getElementById('id_laptop');
        const selProy = document.getElementById('id_proyector');
        const wrapMouse = document.getElementById('wrap_mouse');
        const wrapExt = document.getElementById('wrap_extension');
        const useMouse = document.getElementById('use_mouse');
        const useExt = document.getElementById('use_extension');
        const selMouse = document.getElementById('id_mouse');
        const selExt = document.getElementById('id_extension');
        const useParl = document.getElementById('use_parlante');
        const selParl = document.getElementById('id_parlante');
        // Botones de packs
        const btnPackCompleto = document.getElementById('pack-completo');
        const btnPackProy = document.getElementById('pack-proyector');
        const btnPackLap = document.getElementById('pack-laptop');
        const btnPackClear = document.getElementById('pack-limpiar');

        function refreshComplements(){
            const hasLaptop = parseInt(selLaptop.value||'0',10) > 0;
            wrapMouse.style.display = hasLaptop ? '' : 'none';
            if (!hasLaptop) { useMouse.checked = false; selMouse.disabled = true; selMouse.value = '0'; }
            const hasProy = parseInt(selProy.value||'0',10) > 0;
            wrapExt.style.display = hasProy ? '' : 'none';
            if (!hasProy) { useExt.checked = false; selExt.disabled = true; selExt.value = '0'; }
        }
        function toggleSelect(chk, sel){ sel.disabled = !chk.checked; if (!chk.checked) sel.value = '0'; }
        function firstAvailable(select){
            // Selecciona la primera opción válida distinta de 0
            if (!select) return;
            for (let i=0;i<select.options.length;i++){
                const opt = select.options[i];
                if (opt.value && opt.value !== '0'){ select.value = opt.value; break; }
            }
        }

        function updateSummary(){
            var box = document.getElementById('selection-summary');
            if (!box) return;
            box.innerHTML = '';
            function addChip(label){
                var span = document.createElement('span');
                span.className = 'badge bg-light text-dark border';
                span.textContent = label;
                box.appendChild(span);
            }
            // Base
            if (selLaptop && selLaptop.value !== '0'){
                addChip('💻 Laptop');
            }
            if (selProy && selProy.value !== '0'){
                addChip('📽 Proyector');
            }
            // Complementos
            if (useMouse && useMouse.checked && selMouse.value !== '0'){
                addChip('🖱 Mouse');
            }
            if (useExt && useExt.checked && selExt.value !== '0'){
                addChip('🔌 Extensión');
            }
            if (useParl && useParl.checked && selParl.value !== '0'){
                addChip('🔊 Parlante');
            }
        }

        // Acciones rápidas
        if (btnPackCompleto){ btnPackCompleto.addEventListener('click', ()=>{
            firstAvailable(selLaptop);
            firstAvailable(selProy);
            refreshComplements();
            useExt.checked = true; toggleSelect(useExt, selExt); firstAvailable(selExt);
            // Reset parlante opcional
            if (useParl){ useParl.checked = false; toggleSelect(useParl, selParl); }
            updateSummary();
        }); }
        if (btnPackProy){ btnPackProy.addEventListener('click', ()=>{
            selLaptop.value = '0';
            firstAvailable(selProy);
            refreshComplements();
            useExt.checked = true; toggleSelect(useExt, selExt); firstAvailable(selExt);
            if (useMouse){ useMouse.checked = false; toggleSelect(useMouse, selMouse); }
            if (useParl){ useParl.checked = false; toggleSelect(useParl, selParl); }
            updateSummary();
        }); }
        if (btnPackLap){ btnPackLap.addEventListener('click', ()=>{
            firstAvailable(selLaptop);
            selProy.value = '0';
            refreshComplements();
            if (useExt){ useExt.checked = false; toggleSelect(useExt, selExt); }
            if (useParl){ useParl.checked = false; toggleSelect(useParl, selParl); }
            updateSummary();
        }); }
        if (btnPackClear){ btnPackClear.addEventListener('click', ()=>{
            selLaptop.value = '0'; selProy.value = '0';
            if (useMouse){ useMouse.checked = false; }
            if (useExt){ useExt.checked = false; }
            if (useParl){ useParl.checked = false; }
            toggleSelect(useMouse, selMouse);
            toggleSelect(useExt, selExt);
            toggleSelect(useParl, selParl);
            refreshComplements();
            updateSummary();
        }); }
        // Solo Parlante
        const btnPackParl = document.getElementById('pack-parlante');
        if (btnPackParl){ btnPackParl.addEventListener('click', ()=>{
            selLaptop.value = '0'; selProy.value = '0';
            refreshComplements();
            if (useMouse){ useMouse.checked = false; toggleSelect(useMouse, selMouse); }
            if (useExt){ useExt.checked = false; toggleSelect(useExt, selExt); }
            if (useParl){
                useParl.checked = true; toggleSelect(useParl, selParl); firstAvailable(selParl);
            }
            updateSummary();
        }); }

        selLaptop.addEventListener('change', refreshComplements);
        selProy.addEventListener('change', refreshComplements);
        if (useMouse) useMouse.addEventListener('change', ()=>{ toggleSelect(useMouse, selMouse); updateSummary(); });
        if (useExt) useExt.addEventListener('change', ()=>{ toggleSelect(useExt, selExt); updateSummary(); });
        if (useParl) useParl.addEventListener('change', ()=>{ toggleSelect(useParl, selParl); updateSummary(); });
        // Actualizar resumen ante cambios de selects
        ['id_laptop','id_proyector','id_mouse','id_extension','id_parlante'].forEach(function(id){
            var el = document.getElementById(id);
            if (el){ el.addEventListener('change', updateSummary); }
        });
        refreshComplements();
        updateSummary();
    })();
</script>
</body>
</html>
