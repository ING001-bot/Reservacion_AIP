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

    // Métodos públicos para acceder a datos del modelo
    public function obtenerPrestamoPorId($idPrestamo) {
        return $this->model->obtenerPrestamoPorId($idPrestamo);
    }

    public function getDb() {
        return $this->model->getDb();
    }

    public function listarUsuariosPorRol($roles) {
        return $this->model->listarUsuariosPorRol($roles);
    }

    // Procesar devolución individual
    public function procesarDevolucion($id) {
        if (!ctype_digit($id)) {
            $this->mensaje = "❌ ID de préstamo inválido.";
            return false;
        }

        $comentario = trim((string)($_POST['comentario'] ?? $_GET['comentario'] ?? ''));

        if ($this->model->devolverEquipo($id, $comentario)) {
            $this->mensaje = "✅ Equipo devuelto correctamente.";

            // Notificaciones con el nuevo sistema
            try {
                $ns = new NotificationService();
                $db = $this->model->getDb();
                
                // Obtener datos del préstamo
                $prestamo = $this->model->obtenerPrestamoPorId((int)$id);
                if (!$prestamo) {
                    throw new \Exception("Préstamo no encontrado");
                }
                
                $idProfesor = (int)($prestamo['id_usuario'] ?? 0);
                $nombreEquipo = $prestamo['nombre_equipo'] ?? 'Equipo';
                $nombreProf = $prestamo['nombre'] ?? 'Docente';
                
                $datosDev = [
                    'id_prestamo' => $id,
                    'equipos' => [['nombre' => $nombreEquipo]],
                    'encargado' => $_SESSION['usuario'] ?? 'Encargado',
                    'hora_confirmacion' => date('H:i')
                ];
                
                // Notificar al profesor
                if ($idProfesor) {
                    $ns->crearNotificacionDevolucionPack(
                        $db,
                        $idProfesor,
                        'Profesor',
                        $datosDev
                    );
                }
                
                // Notificar a todos los administradores
                $admins = $this->model->listarUsuariosPorRol(['Administrador']);
                foreach ($admins as $admin) {
                    $ns->crearNotificacionDevolucionPack(
                        $db,
                        (int)$admin['id_usuario'],
                        'Administrador',
                        $datosDev
                    );
                }
                
                // Mantener el sistema de correos existente para admins
                $obs = $comentario !== '' ? $comentario : '(sin observación)';
                $subjectAdmin = 'Devolución confirmada - ' . $nombreProf;
                $msgAdmin = 'El encargado confirmó la devolución de un equipo.<br><br>' .
                            '<strong>Profesor:</strong> ' . htmlspecialchars($nombreProf) . '<br>' .
                            '<strong>Equipo:</strong> ' . htmlspecialchars($nombreEquipo) . '<br>' .
                            '<strong>Observación:</strong> ' . nl2br(htmlspecialchars($obs));
                foreach ($admins as $u) {
                    if (!empty($u['correo'])) {
                        $ns->sendNotification(
                            ['email' => $u['correo']],
                            $subjectAdmin,
                            $msgAdmin,
                            [ 'userName' => ($u['nombre'] ?? 'Administrador'), 'type' => 'info', 'sendSms' => false ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                error_log("Error al crear notificaciones de devolución: " . $e->getMessage());
            }
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
