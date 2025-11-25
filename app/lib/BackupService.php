<?php
namespace App\Lib;

class BackupService {
    private $db;
    private $backupDir;
    
    public function __construct($conexion = null) {
        if ($conexion === null) {
            global $conexion;
        }
        $this->db = $conexion;
        $this->backupDir = __DIR__ . '/../../backups/database/';
        $this->ensureBackupDirectory();
    }
    
    /**
     * Crear directorio de backups si no existe
     */
    private function ensureBackupDirectory(): void {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Crear .htaccess para proteger backups
        $htaccess = $this->backupDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
    
    /**
     * Crear backup completo de la base de datos
     */
    public function crearBackupCompleto(): array {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_completo_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            // Obtener todas las tablas
            $stmt = $this->db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $sql = "-- Backup completo de la base de datos\n";
            $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Generado por Sistema AIP\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                // Estructura de la tabla
                $createTableStmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
                $createTable = $createTableStmt->fetch(\PDO::FETCH_ASSOC);
                
                $sql .= "-- Tabla: {$table}\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                // Datos de la tabla
                $rowsStmt = $this->db->query("SELECT * FROM `{$table}`");
                $rows = $rowsStmt->fetchAll(\PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $sql .= "-- Datos de {$table}\n";
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            return "'" . addslashes($value) . "'";
                        }, array_values($row));
                        
                        $columns = '`' . implode('`, `', array_keys($row)) . '`';
                        $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Guardar archivo
            file_put_contents($filepath, $sql);
            
            // Comprimir
            $zipPath = $filepath . '.zip';
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                    $zip->addFile($filepath, $filename);
                    $zip->close();
                    unlink($filepath); // Eliminar SQL sin comprimir
                    $filepath = $zipPath;
                    $filename .= '.zip';
                }
            }
            
            return [
                'error' => false,
                'mensaje' => '✅ Backup creado exitosamente',
                'archivo' => $filename,
                'ruta' => $filepath,
                'tamaño' => $this->formatBytes(filesize($filepath))
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'mensaje' => '❌ Error al crear backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear backup automático (solo tablas críticas)
     */
    public function crearBackupAutomatico(): array {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_auto_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            // Solo tablas críticas
            $tablasCriticas = ['usuarios', 'configuracion_usuario', 'equipos', 'aulas', 'tipo_equipo'];
            
            $sql = "-- Backup automático\n";
            $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tablasCriticas as $table) {
                // Verificar si existe la tabla
                $check = $this->db->query("SHOW TABLES LIKE '{$table}'")->rowCount();
                if ($check === 0) continue;
                
                $createTableStmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
                $createTable = $createTableStmt->fetch(\PDO::FETCH_ASSOC);
                
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                $rowsStmt = $this->db->query("SELECT * FROM `{$table}`");
                $rows = $rowsStmt->fetchAll(\PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            return "'" . addslashes($value) . "'";
                        }, array_values($row));
                        
                        $columns = '`' . implode('`, `', array_keys($row)) . '`';
                        $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            file_put_contents($filepath, $sql);
            
            return [
                'error' => false,
                'mensaje' => '✅ Backup automático creado',
                'archivo' => $filename
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'mensaje' => '❌ Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar todos los backups disponibles
     */
    public function listarBackups(): array {
        $backups = [];
        $files = glob($this->backupDir . 'backup_*.{sql,zip}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $backups[] = [
                'nombre' => basename($file),
                'fecha' => date('Y-m-d H:i:s', filemtime($file)),
                'tamaño' => $this->formatBytes(filesize($file)),
                'ruta' => $file
            ];
        }
        
        // Ordenar por fecha descendente
        usort($backups, function($a, $b) {
            return strcmp($b['fecha'], $a['fecha']);
        });
        
        return $backups;
    }
    
    /**
     * Restaurar backup
     */
    public function restaurarBackup(string $filename): array {
        try {
            $filepath = $this->backupDir . $filename;
            
            if (!file_exists($filepath)) {
                return ['error' => true, 'mensaje' => '❌ Archivo no encontrado'];
            }
            
            // Si es ZIP, descomprimir
            if (pathinfo($filepath, PATHINFO_EXTENSION) === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($filepath) === true) {
                    $sqlFile = $this->backupDir . 'temp_restore.sql';
                    $zip->extractTo($this->backupDir);
                    $zip->close();
                    
                    // Buscar el archivo .sql extraído
                    $extractedFiles = glob($this->backupDir . 'backup_*.sql');
                    if (!empty($extractedFiles)) {
                        $filepath = $extractedFiles[0];
                    }
                }
            }
            
            // Leer y ejecutar SQL
            $sql = file_get_contents($filepath);
            
            // Ejecutar por lotes (separar por ;)
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^--/', $stmt);
                }
            );
            
            $this->db->beginTransaction();
            
            foreach ($statements as $statement) {
                $this->db->exec($statement);
            }
            
            $this->db->commit();
            
            // Limpiar archivo temporal
            if (isset($sqlFile) && file_exists($sqlFile)) {
                @unlink($sqlFile);
            }
            
            return [
                'error' => false,
                'mensaje' => '✅ Backup restaurado correctamente'
            ];
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'error' => true,
                'mensaje' => '❌ Error al restaurar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar backup antiguo
     */
    public function eliminarBackup(string $filename): array {
        try {
            $filepath = $this->backupDir . $filename;
            
            if (!file_exists($filepath)) {
                return ['error' => true, 'mensaje' => '❌ Archivo no encontrado'];
            }
            
            unlink($filepath);
            
            return ['error' => false, 'mensaje' => '✅ Backup eliminado'];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'mensaje' => '❌ Error al eliminar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpiar backups antiguos (mantener solo los últimos N)
     */
    public function limpiarBackupsAntiguos(int $mantener = 10): array {
        try {
            $backups = $this->listarBackups();
            $eliminados = 0;
            
            if (count($backups) > $mantener) {
                $aEliminar = array_slice($backups, $mantener);
                
                foreach ($aEliminar as $backup) {
                    if (unlink($backup['ruta'])) {
                        $eliminados++;
                    }
                }
            }
            
            return [
                'error' => false,
                'mensaje' => "✅ {$eliminados} backup(s) antiguo(s) eliminado(s)"
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'mensaje' => '❌ Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Descargar backup
     */
    public function descargarBackup(string $filename): void {
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            die('Archivo no encontrado');
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}
