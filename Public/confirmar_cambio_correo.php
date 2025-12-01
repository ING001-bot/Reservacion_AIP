<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/UsuarioModel.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$model = new UsuarioModel();

$mensaje = '';
$exito = false;

if ($token === '') {
    $mensaje = 'Token no proporcionado.';
} else {
    try {
        $res = $model->confirmarCambioCorreoPorToken($token);
        if (!empty($res['ok'])) {
            $exito = true;
            $mensaje = 'Tu correo fue cambiado correctamente.';
            // Actualizar sesión si corresponde
            if (isset($_SESSION['id_usuario']) && isset($res['id_usuario']) && (int)$_SESSION['id_usuario'] === (int)$res['id_usuario']) {
                if (!empty($res['correo'])) {
                    $_SESSION['correo'] = $res['correo'];
                }
            }
        } else {
            $mensaje = $res['mensaje'] ?? 'No se pudo confirmar el cambio de correo.';
        }
    } catch (Throwable $e) {
        $mensaje = 'Error procesando la solicitud.';
    }
}

// Detectar ruta de retorno según rol si hay sesión
$back = '/Reservacion_AIP/app/view/Profesor.php';
if (isset($_SESSION['tipo'])) {
    if ($_SESSION['tipo'] === 'Administrador') {
        $back = '/Reservacion_AIP/app/view/Configuracion_Admin.php';
    } elseif ($_SESSION['tipo'] === 'Encargado') {
        $back = '/Reservacion_AIP/app/view/Configuracion_Encargado.php';
    } else {
        $back = '/Reservacion_AIP/app/view/Configuracion_Profesor.php';
    }
}
// Ya no redirigimos automáticamente; solo mostramos el estado en pantalla
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confirmación de cambio de correo</title>
    <link rel="stylesheet" href="/Reservacion_AIP/Public/assets/bootstrap.min.css" />
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f6f7fb; }
        .card { max-width:520px; width:100%; }
    </style>
</head>
<body>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Confirmación de correo</h5>
            <div class="alert <?php echo $exito ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            
        </div>
    </div>
</body>
</html>
