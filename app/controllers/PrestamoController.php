<?
require 'app/models/PrestamoModel.php';

$prestamoModel = new PrestamoModel($conexion);

// Insertar un préstamo (como ya lo tienes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipo = $_POST['equipo'] ?? null;
    $usuario = $_SESSION['usuario'];

    if ($equipo) {
        $usuarioData = $prestamoModel->obtenerUsuarioPorNombre($usuario);

        if ($usuarioData) {
            $id_usuario = $usuarioData['id_usuario'];
            $fecha = date('Y-m-d');

            $prestamoModel->insertarPrestamo($id_usuario, $equipo, $fecha);
            $prestamoModel->actualizarEstadoEquipo($equipo, 'Prestado');

            $mensaje = "✅ Préstamo registrado correctamente.";
            $mensaje_tipo = 'exito';
        } else {
            $mensaje = "❌ Usuario no encontrado.";
            $mensaje_tipo = 'error';
        }
    } else {
        $mensaje = "⚠️ Debes seleccionar un equipo.";
        $mensaje_tipo = 'advertencia';
    }
}

// Obtener equipos disponibles
$equipos = $prestamoModel->obtenerEquiposDisponibles();
?>