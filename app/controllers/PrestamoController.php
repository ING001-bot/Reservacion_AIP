<?php
require '../models/PrestamoModel.php';
require_once __DIR__ . '/../lib/Mailer.php';
use App\Lib\Mailer;

class PrestamoController {
    private $model;

    public function __construct($conexion) {
        $this->model = new PrestamoModel($conexion);
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin = null, $id_aula) {
        $res = $this->model->guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin, $id_aula);
        if (($res['tipo'] ?? '') === 'success') {
            // Notificar por correo al usuario
            try {
                $to = $_SESSION['correo'] ?? '';
                if ($to) {
                    $equipoIds = array_filter($equipos);
                    $lista = $this->model->obtenerEquiposPorIds($equipoIds);
                    $aula = $this->model->obtenerAulaPorId($id_aula);
                    $aulaNombre = $aula['nombre_aula'] ?? ('Aula #' . $id_aula);
                    $items = '';
                    foreach ($lista as $it) {
                        $items .= '<li>' . htmlspecialchars($it['nombre_equipo']) . ' <small>(' . htmlspecialchars($it['tipo_equipo']) . ')</small></li>';
                    }
                    $subject = 'Confirmación de préstamo de equipos';
                    $html = '<p>Hola ' . htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') . ',</p>' .
                            '<p>Registraste un préstamo con estos detalles:</p>' .
                            '<ul>' .
                            '<li><strong>Aula:</strong> ' . htmlspecialchars($aulaNombre) . '</li>' .
                            '<li><strong>Fecha:</strong> ' . htmlspecialchars($fecha_prestamo) . '</li>' .
                            '<li><strong>Hora inicio:</strong> ' . htmlspecialchars($hora_inicio) . '</li>' .
                            '<li><strong>Hora fin:</strong> ' . htmlspecialchars($hora_fin ?: '-') . '</li>' .
                            '<li><strong>Equipos:</strong><ul>' . $items . '</ul></li>' .
                            '</ul>' .
                            '<p>Si no fuiste tú, por favor contacta al administrador.</p>';
                    $mailer = new Mailer();
                    $mailer->send($to, $subject, $html);
                }
            } catch (\Throwable $e) {
                error_log('Email prestamo fallo: ' . $e->getMessage());
            }
        }
        return $res;
    }

    public function listarEquiposPorTipo($tipo) {
        return $this->model->listarEquiposPorTipo($tipo);
    }

    public function obtenerTodosPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }

    public function listarPrestamosPorUsuario($id_usuario) {
        return $this->model->listarPrestamosPorUsuario($id_usuario);
    }

    public function devolverEquipo($id_prestamo) {
        $ok = $this->model->devolverEquipo($id_prestamo);
        if ($ok) {
            try {
                $to = $_SESSION['correo'] ?? '';
                if ($to) {
                    $subject = 'Confirmación de devolución de equipo';
                    $html = '<p>Hola ' . htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') . ',</p>' .
                            '<p>Se registró la devolución del préstamo #' . htmlspecialchars((string)$id_prestamo) . '.</p>' .
                            '<p>Gracias por tu responsabilidad.</p>';
                    $mailer = new Mailer();
                    $mailer->send($to, $subject, $html);
                }
            } catch (\Throwable $e) {
                error_log('Email devolucion fallo: ' . $e->getMessage());
            }
        }
        return $ok;
    }
}
?>
