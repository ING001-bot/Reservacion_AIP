<?php
require_once '../models/PrestamoModel.php';
require_once __DIR__ . '/../lib/NotificationService.php';
use App\Lib\NotificationService;

class PrestamoController {
    private $model;
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->model = new PrestamoModel($conexion);
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin = null, $id_aula) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['tipo'] ?? '') === 'Profesor') {
            if (empty($_SESSION['otp_verified'])) {
                return ['tipo'=>'error','mensaje'=>'Debes verificar tu identidad con el código SMS antes de confirmar el préstamo.'];
            }
        }
        $res = $this->model->guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin, $id_aula);
        if (($res['tipo'] ?? '') === 'success') {
            // Construir detalles del préstamo (equipos por nombre)
            $detalles = '';
            try {
                $eqs = $this->model->obtenerEquiposPorIds($equipos);
                if ($eqs) {
                    $nombres = array_map(function($e){ return $e['nombre_equipo'] ?? 'Equipo'; }, $eqs);
                    $detalles = 'Equipos: ' . implode(', ', $nombres) . '. ';
                }
            } catch (\Throwable $e) { /* noop */ }

            // Confirmación al profesor: correo + campanita
            try {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $docente = $_SESSION['usuario'] ?? 'Docente';
                $correoDoc = $_SESSION['correo'] ?? '';
                // campanita al profesor
                try {
                    $titulo = 'Préstamo registrado';
                    $mensaje = 'Tu préstamo fue registrado. ' . $detalles . 'Fecha: ' . $fecha_prestamo . ', ' . $hora_inicio . '-' . ($hora_fin?:'-') . '.';
                    $this->model->crearNotificacion((int)$id_usuario, $titulo, $mensaje, 'Profesor.php?view=notificaciones');
                } catch (\Throwable $e) { /* noop */ }
                // correo al profesor
                if (!empty($correoDoc)) {
                    $ns = new NotificationService();
                    $ns->sendNotification(
                        ['email' => $correoDoc],
                        'Confirmación de préstamo de equipos',
                        nl2br(htmlspecialchars('Hola ' . $docente . ', se registró tu préstamo. ' . $detalles . 'Fecha: ' . $fecha_prestamo . ', ' . $hora_inicio . '-' . ($hora_fin?:'-') . '.')),
                        [
                            'userName' => $docente,
                            'type' => 'success',
                            'sendSms' => false,
                            'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/app/view/Profesor.php?view=notificaciones'
                        ]
                    );
                }
            } catch (\Throwable $e) { /* log suave */ }

            // Notificar Admin y Encargado: correo + campanita
            try {
                // Obtener el último préstamo creado para obtener su ID
                $ultimoPrestamo = $this->model->obtenerUltimoPrestamoPorUsuario($id_usuario);
                $id_prestamo = $ultimoPrestamo['id_prestamo'] ?? null;
                
                $usuarios = $this->model->listarUsuariosPorRol(['Administrador','Encargado']);
                $docente = $_SESSION['usuario'] ?? 'Docente';
                $mensaje = 'Nuevo préstamo registrado por ' . $docente . '. ' . $detalles . 'Fecha: ' . $fecha_prestamo . ', ' . $hora_inicio . '-' . ($hora_fin?:'-') . '.';
                foreach ($usuarios as $u) {
                    // Determinar URL según el rol
            $urlNotif = ($u['tipo_usuario'] === 'Administrador') 
                ? 'Admin.php?view=notificaciones' 
                : 'Encargado.php?view=notificaciones';
                    
                    // Metadata con id_prestamo
                    $metadata = json_encode(['id_prestamo' => $id_prestamo]);
                    
                    // Campanita con metadata
                    $this->model->crearNotificacionConMetadata((int)$u['id_usuario'], 'Nuevo préstamo de equipos', $mensaje, $urlNotif, $metadata);
                    
                    // Correo
                    if (!empty($u['correo'])) {
                        $ns = new NotificationService();
                        // URL correcta según el rol
                        $emailUrl = ($u['tipo_usuario'] === 'Administrador') 
                            ? 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/app/view/Admin.php?view=notificaciones'
                            : 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/app/view/Encargado.php?view=notificaciones';
                        
                        $ns->sendNotification(
                            ['email' => $u['correo']],
                            'Nuevo préstamo de equipos',
                            nl2br(htmlspecialchars($mensaje)),
                            [
                                'userName' => ($u['nombre'] ?? 'Usuario'),
                                'type' => 'info',
                                'sendSms' => false,
                                'url' => $emailUrl
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) { /* log suave */ }
        }
        return $res;
    }

    public function listarEquiposPorTipo($tipo) {
        return $this->model->listarEquiposPorTipo($tipo);
    }
    
    public function listarEquiposPorTipoConStock($tipo, $fecha) {
        return $this->model->listarEquiposPorTipoConStock($tipo, $fecha);
    }
    
    public function listarTodosEquipos() {
        return $this->model->listarTodosEquipos();
    }
    
    /**
     * Obtiene todos los tipos de equipos activos con su inventario disponible
     * @param string $fecha Fecha para verificar disponibilidad
     * @return array Array de tipos con sus equipos
     */
    public function listarTodosLosTiposConStock($fecha) {
        try {
            // Obtener todos los tipos distintos de equipos activos
            $query = "SELECT DISTINCT tipo_equipo FROM equipos WHERE activo = 1 ORDER BY tipo_equipo";
            $stmt = $this->conexion->prepare($query);
            $stmt->execute();
            $tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $resultado = [];
            foreach ($tipos as $tipo) {
                $equipos = $this->listarEquiposPorTipoConStock($tipo, $fecha);
                if (!empty($equipos)) {
                    $resultado[$tipo] = [
                        'equipos' => $equipos,
                        'total_disponible' => array_sum(array_column($equipos, 'disponible'))
                    ];
                }
            }
            
            return $resultado;
        } catch (PDOException $e) {
            error_log("Error en listarTodosLosTiposConStock: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerTodosPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }

    public function listarPrestamosPorUsuario($id_usuario) {
        return $this->model->listarPrestamosPorUsuario($id_usuario);
    }

    public function devolverEquipo($id_prestamo, ?string $comentario = null, $enviarNotificacion = false) {
        $ok = $this->model->devolverEquipo($id_prestamo, $comentario);
        if ($ok && $enviarNotificacion) {
            // Solo enviar notificación si se solicita explícitamente
            // Por defecto no envía para permitir notificaciones agrupadas
            try {
                $idProfesor = $this->model->obtenerUsuarioPorPrestamo((int)$id_prestamo);
                if ($idProfesor) {
                    $titulo = 'Devolución registrada';
                    $mensaje = 'Tu préstamo #'.(int)$id_prestamo.' fue marcado como devuelto.' . ($comentario ? (' Observación: '.$comentario) : '');
                    $this->model->crearNotificacion($idProfesor, $titulo, $mensaje, 'Profesor.php?view=notificaciones');
                }
            } catch (\Throwable $e) { /* log suave */ }
        }
        return $ok;
    }
    
    public function obtenerPrestamoPorId($id_prestamo) {
        return $this->model->obtenerPrestamoPorId($id_prestamo);
    }
    
    public function getDb() {
        return $this->model->getDb();
    }
    
    public function listarUsuariosPorRol($roles) {
        return $this->model->listarUsuariosPorRol($roles);
    }

    public function obtenerPrestamosFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        return $this->model->obtenerPrestamosFiltrados($estado, $desde, $hasta, $q);
    }

    // Obtener TODAS las devoluciones sin límite de fecha
    public function obtenerTodasLasDevoluciones(): array {
        return $this->model->obtenerTodasLasDevoluciones();
    }

    // ========================= Notificaciones (in-app) =========================
    public function listarNotificacionesUsuario(int $id_usuario, bool $soloNoLeidas = false, int $limit = 10): array {
        return $this->model->listarNotificacionesUsuario($id_usuario, $soloNoLeidas, $limit);
    }

    public function marcarNotificacionLeida(int $id_notificacion, int $id_usuario): bool {
        return $this->model->marcarNotificacionLeida($id_notificacion, $id_usuario);
    }

    public function marcarTodasNotificacionesLeidas(int $id_usuario): bool {
        return $this->model->marcarTodasNotificacionesLeidas($id_usuario);
    }
}
?>
