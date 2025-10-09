<?php
require_once '../models/PrestamoModel.php';
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
            // Notificaciones internas: Admin y Encargado
            try {
                $aula = $this->model->obtenerAulaPorId($id_aula);
                $aulaNombre = $aula['nombre_aula'] ?? ('Aula #' . $id_aula);
                $usuarios = $this->model->listarUsuariosPorRol(['Administrador','Encargado']);
                $equipoIds = array_filter($equipos);
                $lista = $this->model->obtenerEquiposPorIds($equipoIds);
                $chips = array_map(function($it){ return ($it['nombre_equipo'].' ('.$it['tipo_equipo'].')'); }, $lista);
                $msg = 'Nuevo préstamo registrado por '.($_SESSION['usuario'] ?? 'Usuario').". Aula: $aulaNombre. Fecha: $fecha_prestamo, $hora_inicio-".($hora_fin?:'-').'. Equipos: '.implode(', ', $chips);
                foreach ($usuarios as $u) {
                    $this->model->crearNotificacion((int)$u['id_usuario'], 'Nuevo préstamo de equipos', $msg, 'Admin.php?view=historial_global');
                }
            } catch (\Throwable $e) { /* log suave */ }
            // Notificar por correo al usuario (async)
            $to = $_SESSION['correo'] ?? '';
            if ($to) {
                $equipoIds = array_filter($equipos);
                $lista = $this->model->obtenerEquiposPorIds($equipoIds);
                $aula = $this->model->obtenerAulaPorId($id_aula);
                $aulaNombre = $aula['nombre_aula'] ?? ('Aula #' . $id_aula);
                $usuario = $_SESSION['usuario'] ?? 'Usuario';
                register_shutdown_function(function() use ($to, $lista, $aulaNombre, $usuario, $fecha_prestamo, $hora_inicio, $hora_fin) {
                    try {
                        $items = '';
                        foreach ($lista as $it) {
                            $items .= '<li>' . htmlspecialchars($it['nombre_equipo']) . ' <small>(' . htmlspecialchars($it['tipo_equipo']) . ')</small></li>';
                        }
                        $mailer = new Mailer();
                        $subject = 'Confirmación de préstamo de equipos';
                        $html = '<p>Hola ' . htmlspecialchars($usuario) . ',</p>' .
                                '<p>Registraste un préstamo con estos detalles:</p>' .
                                '<ul>' .
                                '<li><strong>Aula:</strong> ' . htmlspecialchars($aulaNombre) . '</li>' .
                                '<li><strong>Fecha:</strong> ' . htmlspecialchars($fecha_prestamo) . '</li>' .
                                '<li><strong>Hora inicio:</strong> ' . htmlspecialchars($hora_inicio) . '</li>' .
                                '<li><strong>Hora fin:</strong> ' . htmlspecialchars($hora_fin ?: '-') . '</li>' .
                                '<li><strong>Equipos:</strong><ul>' . $items . '</ul></li>' .
                                '</ul>' .
                                '<p>Si no fuiste tú, por favor contacta al administrador.</p>';
                        $mailer->send($to, $subject, $html);
                    } catch (\Throwable $e) {
                        error_log('Email prestamo fallo: ' . $e->getMessage());
                    }
                });
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

    public function devolverEquipo($id_prestamo, ?string $comentario = null) {
        $ok = $this->model->devolverEquipo($id_prestamo, $comentario);
        if ($ok) {
            // Obtener datos del préstamo y profesor
            $idProfesor = $this->model->obtenerUsuarioPorPrestamo((int)$id_prestamo);
            $profesor = null;
            if ($idProfesor) {
                $profesor = $this->model->obtenerUsuarioPorId($idProfesor);
            }
            
            // Notificar al profesor (sistema)
            try {
                if ($idProfesor) {
                    $titulo = 'Devolución registrada';
                    $mensaje = 'Tu préstamo #'.(int)$id_prestamo.' fue marcado como devuelto por '.($_SESSION['usuario'] ?? 'Encargado').'.'.($comentario ? (' Observación: '.$comentario) : '');
                    $this->model->crearNotificacion($idProfesor, $titulo, $mensaje, 'Historial.php');
                }
            } catch (\Throwable $e) { /* log suave */ }
            
            // Notificar al profesor (correo)
            try {
                if ($profesor && !empty($profesor['correo'])) {
                    $subject = 'Confirmación de devolución de equipo';
                    $html = '<p>Hola ' . htmlspecialchars($profesor['nombre'] ?? 'Docente') . ',</p>' .
                            '<p>Se registró la devolución del préstamo #' . htmlspecialchars((string)$id_prestamo) . ' por '.htmlspecialchars($_SESSION['usuario'] ?? 'Encargado').'.</p>' .
                            ($comentario ? ('<p><strong>Observación:</strong><br>' . nl2br(htmlspecialchars($comentario)) . '</p>') : '') .
                            '<p>Gracias por tu responsabilidad.</p>';
                    $mailer = new Mailer();
                    $mailer->send($profesor['correo'], $subject, $html);
                }
            } catch (\Throwable $e) {
                error_log('Email devolucion profesor fallo: ' . $e->getMessage());
            }
            
            // Notificar al Administrador (sistema + correo)
            try {
                $admins = $this->model->listarUsuariosPorRol(['Administrador']);
                $nombreProf = $profesor['nombre'] ?? 'Docente';
                $estadoTxt = $comentario ? $comentario : 'Equipos devueltos correctamente';
                foreach ($admins as $admin) {
                    $titulo = 'Devolución registrada por Encargado';
                    $msg = 'El profesor '.$nombreProf.' devolvió el préstamo #'.(int)$id_prestamo.'. Estado: '.$estadoTxt;
                    $this->model->crearNotificacion((int)$admin['id_usuario'], $titulo, $msg, 'Admin.php?view=devolucion');
                    
                    if (!empty($admin['correo'])) {
                        $subject = 'Devolución registrada - Préstamo #'.(int)$id_prestamo;
                        $html = '<p>Hola ' . htmlspecialchars($admin['nombre']) . ',</p>' .
                                '<p>El encargado '.htmlspecialchars($_SESSION['usuario'] ?? 'Encargado').' registró la devolución del préstamo #' . htmlspecialchars((string)$id_prestamo) . '.</p>' .
                                '<ul>' .
                                '<li><strong>Profesor:</strong> ' . htmlspecialchars($nombreProf) . '</li>' .
                                '<li><strong>Estado:</strong> ' . htmlspecialchars($estadoTxt) . '</li>' .
                                '</ul>';
                        $mailer = new Mailer();
                        $mailer->send($admin['correo'], $subject, $html);
                    }
                }
            } catch (\Throwable $e) {
                error_log('Email devolucion admin fallo: ' . $e->getMessage());
            }
        }
        return $ok;
    }

    public function obtenerPrestamosFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        return $this->model->obtenerPrestamosFiltrados($estado, $desde, $hasta, $q);
    }

    // ========================= NUEVO: Packs de préstamo =========================
    public function guardarPrestamoPack(int $id_usuario, int $id_aula, string $fecha_prestamo, string $hora_inicio, ?string $hora_fin, array $items): array {
        $res = $this->model->crearPrestamoPack($id_usuario, $id_aula, $fecha_prestamo, $hora_inicio, $hora_fin, $items);
        if (($res['tipo'] ?? '') === 'success') {
            // Notificaciones internas: Admin y Encargado
            try {
                $aula = $this->model->obtenerAulaPorId($id_aula);
                $aulaNombre = $aula['nombre_aula'] ?? ('Aula #' . $id_aula);
                $usuarios = $this->model->listarUsuariosPorRol(['Administrador','Encargado']);
                $chips = [];
                foreach ($items as $it) {
                    if (($it['cantidad'] ?? 0) > 0) {
                        $chips[] = $it['tipo'].' x'.(int)$it['cantidad'];
                    }
                }
                $msg = 'Nuevo préstamo (pack) por '.($_SESSION['usuario'] ?? 'Usuario').". Aula: $aulaNombre. Fecha: $fecha_prestamo, $hora_inicio-".($hora_fin?:'-').'. Detalle: '.implode(', ', $chips);
                foreach ($usuarios as $u) {
                    $this->model->crearNotificacion((int)$u['id_usuario'], 'Nuevo préstamo (pack)', $msg, 'Admin.php?view=historial_global');
                }
            } catch (\Throwable $e) { /* log suave */ }
            // Notificar por correo al usuario (async)
            $to = $_SESSION['correo'] ?? '';
            if ($to) {
                $aula = $this->model->obtenerAulaPorId($id_aula);
                $aulaNombre = $aula['nombre_aula'] ?? ('Aula #' . $id_aula);
                $usuario = $_SESSION['usuario'] ?? 'Usuario';
                register_shutdown_function(function() use ($to, $items, $aulaNombre, $usuario, $fecha_prestamo, $hora_inicio, $hora_fin) {
                    try {
                        $itemsHtml = '';
                        foreach ($items as $it) {
                            if (($it['cantidad'] ?? 0) > 0) {
                                $label = htmlspecialchars($it['tipo']);
                                if (!empty($it['es_complemento'])) { $label .= ' (complemento)'; }
                                $itemsHtml .= '<li>' . $label . ' x' . (int)$it['cantidad'] . '</li>';
                            }
                        }
                        $mailer = new Mailer();
                        $subject = 'Confirmación de préstamo de equipos';
                        $html = '<p>Hola ' . htmlspecialchars($usuario) . ',</p>' .
                                '<p>Registraste un préstamo con estos detalles:</p>' .
                                '<ul>' .
                                '<li><strong>Aula:</strong> ' . htmlspecialchars($aulaNombre) . '</li>' .
                                '<li><strong>Fecha:</strong> ' . htmlspecialchars($fecha_prestamo) . '</li>' .
                                '<li><strong>Hora inicio:</strong> ' . htmlspecialchars($hora_inicio) . '</li>' .
                                '<li><strong>Hora fin:</strong> ' . htmlspecialchars($hora_fin ?: '-') . '</li>' .
                                '<li><strong>Detalle:</strong><ul>' . $itemsHtml . '</ul></li>' .
                                '</ul>' .
                                '<p>Si no fuiste tú, por favor contacta al administrador.</p>';
                        $mailer->send($to, $subject, $html);
                    } catch (\Throwable $e) {
                        error_log('Email prestamo pack fallo: ' . $e->getMessage());
                    }
                });
            }
        }
        return $res;
    }

    public function listarPacksPorUsuario(int $id_usuario): array {
        return $this->model->listarPacksPorUsuario($id_usuario);
    }

    public function obtenerItemsDePack(int $id_pack): array {
        return $this->model->obtenerItemsDePack($id_pack);
    }

    // Nuevos: soporte para Devolución de packs y filtros
    public function listarPacksFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        return $this->model->listarPacksFiltrados($estado, $desde, $hasta, $q);
    }

    public function devolverPack(int $id_pack, ?string $comentario = null): bool {
        $ok = $this->model->devolverPack($id_pack, $comentario);
        if ($ok) {
            // Obtener datos del pack y profesor
            $idProfesor = $this->model->obtenerUsuarioPorPack((int)$id_pack);
            $profesor = null;
            if ($idProfesor) {
                $profesor = $this->model->obtenerUsuarioPorId($idProfesor);
            }
            
            // Notificar al profesor (sistema)
            try {
                if ($idProfesor) {
                    $titulo = 'Devolución de pack registrada';
                    $mensaje = 'Tu préstamo (pack) #'.(int)$id_pack.' fue marcado como devuelto por '.($_SESSION['usuario'] ?? 'Encargado').'.'.($comentario ? (' Observación: '.$comentario) : '.');
                    $this->model->crearNotificacion($idProfesor, $titulo, $mensaje, 'Historial.php');
                }
            } catch (\Throwable $e) { /* log suave */ }
            
            // Notificar al profesor (correo)
            try {
                if ($profesor && !empty($profesor['correo'])) {
                    $subject = 'Confirmación de devolución de equipos (pack)';
                    $html = '<p>Hola ' . htmlspecialchars($profesor['nombre'] ?? 'Docente') . ',</p>' .
                            '<p>Se registró la devolución del pack #' . htmlspecialchars((string)$id_pack) . ' por '.htmlspecialchars($_SESSION['usuario'] ?? 'Encargado').'.</p>' .
                            ($comentario ? ('<p><strong>Observación:</strong><br>' . nl2br(htmlspecialchars($comentario)) . '</p>') : '') .
                            '<p>Gracias por tu responsabilidad.</p>';
                    $mailer = new Mailer();
                    $mailer->send($profesor['correo'], $subject, $html);
                }
            } catch (\Throwable $e) {
                error_log('Email devolucion pack profesor fallo: ' . $e->getMessage());
            }
            
            // Notificar al Administrador (sistema + correo)
            try {
                $admins = $this->model->listarUsuariosPorRol(['Administrador']);
                $nombreProf = $profesor['nombre'] ?? 'Docente';
                $estadoTxt = $comentario ? $comentario : 'Equipos devueltos correctamente';
                foreach ($admins as $admin) {
                    $titulo = 'Devolución de pack registrada por Encargado';
                    $msg = 'El profesor '.$nombreProf.' devolvió el pack #'.(int)$id_pack.'. Estado: '.$estadoTxt;
                    $this->model->crearNotificacion((int)$admin['id_usuario'], $titulo, $msg, 'Admin.php?view=devolucion');
                    
                    if (!empty($admin['correo'])) {
                        $subject = 'Devolución de pack registrada - Pack #'.(int)$id_pack;
                        $html = '<p>Hola ' . htmlspecialchars($admin['nombre']) . ',</p>' .
                                '<p>El encargado '.htmlspecialchars($_SESSION['usuario'] ?? 'Encargado').' registró la devolución del pack #' . htmlspecialchars((string)$id_pack) . '.</p>' .
                                '<ul>' .
                                '<li><strong>Profesor:</strong> ' . htmlspecialchars($nombreProf) . '</li>' .
                                '<li><strong>Estado:</strong> ' . htmlspecialchars($estadoTxt) . '</li>' .
                                '</ul>';
                        $mailer = new Mailer();
                        $mailer->send($admin['correo'], $subject, $html);
                    }
                }
            } catch (\Throwable $e) {
                error_log('Email devolucion pack admin fallo: ' . $e->getMessage());
            }
        }
        return $ok;
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
