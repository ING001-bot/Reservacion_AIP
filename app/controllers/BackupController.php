<?php
require_once __DIR__ . '/../lib/BackupService.php';
use App\Lib\BackupService;

class BackupController {
    private $backupService;
    
    public function __construct() {
        $this->backupService = new BackupService();
    }
    
    /**
     * Crear backup completo
     */
    public function crearBackup(): array {
        return $this->backupService->crearBackupCompleto();
    }
    
    /**
     * Crear backup automático
     */
    public function crearBackupAutomatico(): array {
        return $this->backupService->crearBackupAutomatico();
    }
    
    /**
     * Listar backups
     */
    public function listarBackups(): array {
        return $this->backupService->listarBackups();
    }
    
    /**
     * Restaurar backup
     */
    public function restaurarBackup(string $filename): array {
        return $this->backupService->restaurarBackup($filename);
    }
    
    /**
     * Eliminar backup
     */
    public function eliminarBackup(string $filename): array {
        return $this->backupService->eliminarBackup($filename);
    }
    
    /**
     * Limpiar backups antiguos
     */
    public function limpiarBackups(int $mantener = 10): array {
        return $this->backupService->limpiarBackupsAntiguos($mantener);
    }
    
    /**
     * Descargar backup
     */
    public function descargarBackup(string $filename): void {
        $this->backupService->descargarBackup($filename);
    }
}
