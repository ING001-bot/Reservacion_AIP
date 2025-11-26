<?php
namespace App\Lib;

use PDO;

// Cargar NotificationService
require_once __DIR__ . '/NotificationService.php';

class AlertService {
    private $db;
    private $notificationService;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->notificationService = null; // Lazy loading
    }
    
    /**
     * Obtener instancia de NotificationService (lazy loading)
     */
    private function getNotificationService() {
        if ($this->notificationService === null) {
            $this->notificationService = new NotificationService();
        }
        return $this->notificationService;
    }

    /**
     * Verificar préstamos vencidos (sin devolución después de hora_fin)
     * @return array Array con préstamos vencidos
     */
    public function verificarPrestamosVencidos() {
        try {
            // Buscar préstamos individuales vencidos sin devolución
            $sql = "SELECT p.id_prestamo, p.id_usuario, p.fecha, p.hora_fin, 
                           u.nombre as solicitante, u.tipo_usuario,
                           e.nombre as equipo_nombre,
                           TIMESTAMPDIFF(MINUTE, CONCAT(p.fecha, ' ', p.hora_fin), NOW()) as minutos_retraso
                    FROM prestamos p
                    INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                    LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
                    WHERE p.estado = 'Activo'
                      AND p.fecha = CURDATE()
                      AND CONCAT(p.fecha, ' ', p.hora_fin) < NOW()
                      AND NOT EXISTS (
                        SELECT 1 FROM devoluciones d 
                        WHERE d.id_prestamo = p.id_prestamo
                      )
                    ORDER BY p.hora_fin ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $prestamosVencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar préstamos de packs vencidos sin devolución
            $sqlPack = "SELECT pp.id_pack, pp.id_usuario, pp.fecha, pp.hora_fin,
                               u.nombre as solicitante, u.tipo_usuario,
                               TIMESTAMPDIFF(MINUTE, CONCAT(pp.fecha, ' ', pp.hora_fin), NOW()) as minutos_retraso,
                               COUNT(ppe.id_equipo) as cantidad_equipos
                        FROM prestamos_pack pp
                        INNER JOIN usuarios u ON pp.id_usuario = u.id_usuario
                        LEFT JOIN prestamos_pack_equipos ppe ON pp.id_pack = ppe.id_pack
                        WHERE pp.estado = 'Activo'
                          AND pp.fecha = CURDATE()
                          AND CONCAT(pp.fecha, ' ', pp.hora_fin) < NOW()
                          AND NOT EXISTS (
                            SELECT 1 FROM devoluciones d 
                            WHERE d.id_pack = pp.id_pack
                          )
                        GROUP BY pp.id_pack
                        ORDER BY pp.hora_fin ASC";
            
            $stmtPack = $this->db->prepare($sqlPack);
            $stmtPack->execute();
            $packsVencidos = $stmtPack->fetchAll(PDO::FETCH_ASSOC);

            return [
                'prestamos' => $prestamosVencidos,
                'packs' => $packsVencidos,
                'total' => count($prestamosVencidos) + count($packsVencidos)
            ];
        } catch (\Exception $e) {
            error_log("Error al verificar préstamos vencidos: " . $e->getMessage());
            return ['prestamos' => [], 'packs' => [], 'total' => 0];
        }
    }

    /**
     * Generar alertas para préstamos vencidos
     * Envía notificaciones a Encargados y Administradores
     * @param array $prestamosVencidos Array de préstamos vencidos
     * @return int Cantidad de notificaciones creadas
     */
    public function generarAlertasPrestamosVencidos($prestamosVencidos) {
        $notificacionesCreadas = 0;

        try {
            // Obtener usuarios encargados y administradores
            $stmtUsuarios = $this->db->prepare(
                "SELECT id_usuario, tipo_usuario FROM usuarios 
                 WHERE tipo_usuario IN ('Encargado', 'Administrador') AND activo = 1"
            );
            $stmtUsuarios->execute();
            $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

            // Verificar que no se hayan enviado alertas en los últimos 15 minutos (evitar spam)
            $stmtCheck = $this->db->prepare(
                "SELECT COUNT(*) as count FROM notificaciones 
                 WHERE titulo LIKE '%Préstamo sin devolver%' 
                 AND creada_en > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
            );
            $stmtCheck->execute();
            $yaAlertoReciente = $stmtCheck->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if ($yaAlertoReciente) {
                return 0; // No enviar más alertas si ya se envió hace menos de 15 min
            }

            // Para cada préstamo vencido
            foreach ($prestamosVencidos['prestamos'] as $prestamo) {
                $datosPrestamo = [
                    'id_prestamo' => $prestamo['id_prestamo'],
                    'solicitante' => $prestamo['solicitante'],
                    'hora_fin' => $prestamo['hora_fin'],
                    'minutos_retraso' => $prestamo['minutos_retraso']
                ];

                // Enviar notificación a cada encargado y admin
                foreach ($usuarios as $usuario) {
                    $resultado = $this->getNotificationService()->crearNotificacionPrestamoVencido(
                        $this->db,
                        $usuario['id_usuario'],
                        $usuario['tipo_usuario'],
                        $datosPrestamo
                    );
                    if ($resultado) {
                        $notificacionesCreadas++;
                    }
                }
            }

            // Para cada pack vencido
            foreach ($prestamosVencidos['packs'] as $pack) {
                $datosPrestamo = [
                    'id_prestamo' => 'Pack-' . $pack['id_pack'],
                    'solicitante' => $pack['solicitante'],
                    'hora_fin' => $pack['hora_fin'],
                    'minutos_retraso' => $pack['minutos_retraso']
                ];

                foreach ($usuarios as $usuario) {
                    $resultado = $this->getNotificationService()->crearNotificacionPrestamoVencido(
                        $this->db,
                        $usuario['id_usuario'],
                        $usuario['tipo_usuario'],
                        $datosPrestamo
                    );
                    if ($resultado) {
                        $notificacionesCreadas++;
                    }
                }
            }

            return $notificacionesCreadas;
        } catch (\Exception $e) {
            error_log("Error al generar alertas de préstamos vencidos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener préstamos vencidos para mostrar en dashboard del encargado
     * @return array
     */
    public function obtenerPrestamosVencidosParaDashboard() {
        $vencidos = $this->verificarPrestamosVencidos();
        
        // Generar alertas automáticamente si hay vencidos
        if ($vencidos['total'] > 0) {
            $this->generarAlertasPrestamosVencidos($vencidos);
        }

        return $vencidos;
    }
}
