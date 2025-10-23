<?php
session_start();
require '../config/conexion.php';
require_once '../models/PrestamoModel.php';
require_once __DIR__ . '/../lib/NotificationService.php';
use App\Lib\NotificationService;

class DevolucionController {
    private $model;
    public $mensaje = "";

    public function __construct($conexion) {
        $this->model = new PrestamoModel($conexion);
    }

    // Procesar devolución
    public function procesarDevolucion($id) {
        if (!ctype_digit($id)) {
            $this->mensaje = "❌ ID de préstamo inválido.";
            return false;
        }

        $comentario = trim((string)($_POST['comentario'] ?? $_GET['comentario'] ?? ''));

        if ($this->model->devolverEquipo($id, $comentario)) {
            $this->mensaje = "✅ Equipo devuelto correctamente.";

            // Notificaciones según lógica: al Administrador (correo+campanita) y al Docente (correo+campanita)
            try {
                $ns = new NotificationService();
                $idProfesor = $this->model->obtenerUsuarioPorPrestamo((int)$id);
                $prof = $idProfesor ? $this->model->obtenerUsuarioPorId((int)$idProfesor) : null;
                $nombreProf = $prof['nombre'] ?? 'Docente';
                $correoProf = $prof['correo'] ?? '';
                $obs = $comentario !== '' ? $comentario : '(sin observación)';

                // Administradores: correo + campanita
                $admins = $this->model->listarUsuariosPorRol(['Administrador']);
                $subjectAdmin = 'Devolución confirmada - ' . $nombreProf;
                $msgAdmin = 'El encargado confirmó la devolución de un equipo.<br><br>' .
                            '<strong>Profesor:</strong> ' . htmlspecialchars($nombreProf) . '<br>' .
                            '<strong>Observación:</strong> ' . nl2br(htmlspecialchars($obs));
                foreach ($admins as $u) {
                    // correo
                    if (!empty($u['correo'])) {
                        $ns->sendNotification(
                            ['email' => $u['correo']],
                            $subjectAdmin,
                            $msgAdmin,
                            [ 'userName' => ($u['nombre'] ?? 'Administrador'), 'type' => 'info', 'sendSms' => false,
                              'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Sistema_reserva_AIP/Admin.php?view=historial_global' ]
                        );
                    }
                    // campanita
                    $this->model->crearNotificacion((int)$u['id_usuario'], 'Devolución confirmada', strip_tags($msgAdmin), 'Admin.php?view=historial_global');
                }

                // Docente: correo + campanita
                if ($idProfesor) {
                    // correo al docente
                    if (!empty($correoProf)) {
                        $ns->sendNotification(
                            ['email' => $correoProf],
                            'Resultado de devolución de equipo',
                            'El encargado registró la devolución de tu equipo.<br><br>' .
                            '<strong>Estado:</strong> Devuelto<br>' .
                            '<strong>Observación:</strong> ' . nl2br(htmlspecialchars($obs)),
                            [ 'userName' => $nombreProf, 'type' => 'success', 'sendSms' => false ]
                        );
                    }
                    // campanita al docente
                    $this->model->crearNotificacion((int)$idProfesor, 'Devolución registrada', 'Se confirmó la devolución de tu equipo. Observación: ' . $obs, 'Historial.php');
                }
            } catch (\Throwable $e) { /* noop */ }
            return true;
        } else {
            $this->mensaje = "❌ Error al devolver el equipo.";
            return false;
        }
    }

    // Obtener todos los préstamos
    public function obtenerPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }
}

// --- Inicializar controlador ---
$controller = new DevolucionController($conexion);

// --- Verificar sesión y rol ---
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['tipo'] !== 'Encargado') {
    echo "Acceso denegado";
    exit();
}

// --- Procesar devolución si llega por GET ---
if (isset($_GET['devolver'])) {
    $controller->procesarDevolucion($_GET['devolver']);
}

// --- Obtener préstamos ---
$prestamos = $controller->obtenerPrestamos();
$mensaje = $controller->mensaje;

// --- Cargar la vista ---
require '../view/Devolucion.php';
