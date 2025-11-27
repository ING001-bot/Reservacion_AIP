<?php
/**
 * SistemaController - Mantenimiento y optimización del sistema
 */

class SistemaController {
    private $db;
    
    public function __construct($db = null) {
        if (!$db) {
            require_once __DIR__ . '/../config/conexion.php';
            global $conexion;
            $this->db = $conexion;
        } else {
            $this->db = $db;
        }
    }
    
    /**
     * Ejecuta mantenimiento completo del sistema
     * Solo permitido para administradores, máximo una vez al mes
     */
    public function ejecutarMantenimientoCompleto($id_admin) {
        try {
            // Verificar que es admin
            $stmt = $this->db->prepare("SELECT tipo_usuario FROM usuarios WHERE id_usuario = ? AND activo = 1");
            $stmt->execute([$id_admin]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || $user['tipo_usuario'] !== 'Administrador') {
                return [
                    'error' => true,
                    'mensaje' => '❌ Solo los administradores pueden ejecutar mantenimiento'
                ];
            }
            
            // Verificar última ejecución (tabla de configuración del sistema)
            $this->crearTablaMantenimientoSiNoExiste();
            
            $stmt = $this->db->query("SELECT ultima_ejecucion FROM mantenimiento_sistema ORDER BY id DESC LIMIT 1");
            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ultimo && !empty($ultimo['ultima_ejecucion'])) {
                $ultimaFecha = strtotime($ultimo['ultima_ejecucion']);
                $ahora = time();
                $diasTranscurridos = ($ahora - $ultimaFecha) / (60 * 60 * 24);
                
                // Permitir solo si han pasado al menos 30 días
                if ($diasTranscurridos < 30) {
                    $diasRestantes = ceil(30 - $diasTranscurridos);
                    return [
                        'error' => true,
                        'mensaje' => "⏳ Debe esperar $diasRestantes días más. El mantenimiento es mensual."
                    ];
                }
            }
            
            $resultados = [];
            
            // 1. Optimizar todas las tablas
            $resultados[] = $this->optimizarBaseDatos();
            
            // 2. Limpiar notificaciones antiguas (más de 3 meses y ya leídas)
            $resultados[] = $this->limpiarNotificacionesAntiguas();
            
            // 3. Generar backup automático
            $resultados[] = $this->generarBackupAutomatico();
            
            // 4. Limpiar sesiones antiguas (si hay tabla de sesiones)
            $resultados[] = $this->limpiarSesiones();
            
            // 5. Recalcular estadísticas
            $resultados[] = $this->recalcularEstadisticas();
            
            // Registrar ejecución
            $stmt = $this->db->prepare("INSERT INTO mantenimiento_sistema (ultima_ejecucion, ejecutado_por) VALUES (NOW(), ?)");
            $stmt->execute([$id_admin]);
            
            return [
                'error' => false,
                'mensaje' => '✅ Mantenimiento completado exitosamente',
                'detalles' => $resultados
            ];
            
        } catch (Exception $e) {
            return [
                'error' => true,
                'mensaje' => '❌ Error durante mantenimiento: ' . $e->getMessage()
            ];
        }
    }
    
    private function crearTablaMantenimientoSiNoExiste() {
        $sql = "CREATE TABLE IF NOT EXISTS mantenimiento_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ultima_ejecucion DATETIME NOT NULL,
            ejecutado_por INT NOT NULL,
            INDEX idx_fecha (ultima_ejecucion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }
    
    private function optimizarBaseDatos() {
        try {
            $tablas = ['usuarios', 'aulas', 'equipos', 'tipos_equipo', 'reservas', 
                      'prestamos', 'notificaciones', 'configuracion'];
            
            $optimizadas = 0;
            foreach ($tablas as $tabla) {
                try {
                    $this->db->exec("OPTIMIZE TABLE $tabla");
                    $optimizadas++;
                } catch (Exception $e) {
                    // Tabla no existe, continuar
                }
            }
            
            return "✅ $optimizadas tablas optimizadas";
        } catch (Exception $e) {
            return "⚠️ Optimización parcial: " . $e->getMessage();
        }
    }
    
    private function limpiarNotificacionesAntiguas() {
        try {
            $stmt = $this->db->prepare("DELETE FROM notificaciones WHERE leida = 1 AND creada_en < DATE_SUB(NOW(), INTERVAL 3 MONTH)");
            $stmt->execute();
            $eliminadas = $stmt->rowCount();
            return "✅ $eliminadas notificaciones antiguas eliminadas";
        } catch (Exception $e) {
            return "⚠️ Error limpiando notificaciones: " . $e->getMessage();
        }
    }
    
    private function generarBackupAutomatico() {
        try {
            require_once __DIR__ . '/../lib/BackupService.php';
            $backupService = new \App\Lib\BackupService($this->db);
            $resultado = $backupService->crearBackupAutomatico();
            
            if ($resultado['error']) {
                return "⚠️ Backup: " . $resultado['mensaje'];
            }
            return "✅ Backup generado";
        } catch (Exception $e) {
            return "⚠️ Backup no disponible";
        }
    }
    
    private function limpiarSesiones() {
        try {
            // Limpiar archivos de sesión antiguos (si están en /tmp)
            $sessionPath = session_save_path();
            if (empty($sessionPath)) {
                $sessionPath = sys_get_temp_dir();
            }
            
            $eliminados = 0;
            if (is_dir($sessionPath)) {
                $files = glob($sessionPath . '/sess_*');
                $now = time();
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file)) > (7 * 24 * 60 * 60)) {
                        @unlink($file);
                        $eliminados++;
                    }
                }
            }
            
            return "✅ $eliminados sesiones antiguas eliminadas";
        } catch (Exception $e) {
            return "⚠️ Limpieza de sesiones parcial";
        }
    }
    
    private function recalcularEstadisticas() {
        try {
            // Limpiar caché de estadísticas si existe
            $cacheDir = __DIR__ . '/../../cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*.cache');
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            return "✅ Caché de estadísticas limpiado";
        } catch (Exception $e) {
            return "⚠️ Recálculo de estadísticas parcial";
        }
    }
    
    /**
     * Obtener información del último mantenimiento
     */
    public function obtenerUltimoMantenimiento() {
        try {
            $this->crearTablaMantenimientoSiNoExiste();
            
            $stmt = $this->db->query("
                SELECT m.ultima_ejecucion, u.nombre as ejecutado_por_nombre
                FROM mantenimiento_sistema m
                LEFT JOIN usuarios u ON m.ejecutado_por = u.id_usuario
                ORDER BY m.id DESC LIMIT 1
            ");
            
            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ultimo) {
                return [
                    'ejecutado' => false,
                    'mensaje' => 'Nunca se ha ejecutado mantenimiento'
                ];
            }
            
            $ultimaFecha = strtotime($ultimo['ultima_ejecucion']);
            $ahora = time();
            $diasTranscurridos = floor(($ahora - $ultimaFecha) / (60 * 60 * 24));
            $diasRestantes = max(0, 30 - $diasTranscurridos);
            
            return [
                'ejecutado' => true,
                'fecha' => $ultimo['ultima_ejecucion'],
                'ejecutado_por' => $ultimo['ejecutado_por_nombre'],
                'dias_transcurridos' => $diasTranscurridos,
                'dias_restantes' => $diasRestantes,
                'puede_ejecutar' => $diasTranscurridos >= 30
            ];
            
        } catch (Exception $e) {
            return [
                'ejecutado' => false,
                'mensaje' => 'Error al consultar: ' . $e->getMessage()
            ];
        }
    }
}
