<?php
require_once __DIR__ . '/../config/conexion.php';

class ConfiguracionModel {
    private $db;

    public function __construct($conexion = null) {
        if ($conexion === null) {
            global $conexion;
        }
        $this->db = $conexion;
        $this->ensureSchema();
    }

    private function ensureSchema(): void {
        try {
            // Crear tabla si no existe
            $this->db->exec("CREATE TABLE IF NOT EXISTS configuracion_usuario (
                id_configuracion INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL UNIQUE,
                foto_perfil VARCHAR(255) NULL,
                bio TEXT NULL,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            error_log('ensureSchema ConfiguracionModel: ' . $e->getMessage());
        }
    }

    /**
     * Obtener configuración de un usuario
     */
    public function obtenerConfiguracion(int $id_usuario): ?array {
        $stmt = $this->db->prepare("SELECT * FROM configuracion_usuario WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crear o actualizar configuración
     */
    public function guardarConfiguracion(int $id_usuario, ?string $foto_perfil = null, ?string $bio = null): bool {
        // Verificar si ya existe
        $existe = $this->obtenerConfiguracion($id_usuario);
        
        if ($existe) {
            // Actualizar solo los campos que se proporcionan
            $sql = "UPDATE configuracion_usuario SET ";
            $params = [];
            $updates = [];
            
            if ($foto_perfil !== null) {
                $updates[] = "foto_perfil = ?";
                $params[] = $foto_perfil;
            }
            
            if ($bio !== null) {
                $updates[] = "bio = ?";
                $params[] = trim($bio);
            }
            
            if (empty($updates)) return true; // No hay nada que actualizar
            
            $sql .= implode(", ", $updates) . " WHERE id_usuario = ?";
            $params[] = $id_usuario;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            // Insertar registro nuevo - usar INSERT con ON DUPLICATE KEY
            // Solo insertar los campos que se proporcionan, sin sobrescribir otros
            if ($foto_perfil !== null && $bio !== null) {
                $stmt = $this->db->prepare("INSERT INTO configuracion_usuario (id_usuario, foto_perfil, bio) VALUES (?, ?, ?)");
                return $stmt->execute([$id_usuario, $foto_perfil, trim($bio)]);
            } elseif ($foto_perfil !== null) {
                $stmt = $this->db->prepare("INSERT INTO configuracion_usuario (id_usuario, foto_perfil) VALUES (?, ?)");
                return $stmt->execute([$id_usuario, $foto_perfil]);
            } elseif ($bio !== null) {
                $stmt = $this->db->prepare("INSERT INTO configuracion_usuario (id_usuario, bio) VALUES (?, ?)");
                return $stmt->execute([$id_usuario, trim($bio)]);
            } else {
                // No hay nada que insertar
                return true;
            }
        }
    }

    /**
     * Actualizar solo la foto de perfil
     */
    public function actualizarFotoPerfil(int $id_usuario, string $ruta_foto): bool {
        return $this->guardarConfiguracion($id_usuario, $ruta_foto, null);
    }

    /**
     * Actualizar solo la bio
     */
    public function actualizarBio(int $id_usuario, string $bio): bool {
        return $this->guardarConfiguracion($id_usuario, null, $bio);
    }

    /**
     * Eliminar foto de perfil
     */
    public function eliminarFotoPerfil(int $id_usuario): bool {
        $config = $this->obtenerConfiguracion($id_usuario);
        
        if ($config && !empty($config['foto_perfil'])) {
            // Eliminar archivo físico
            $rutaCompleta = __DIR__ . '/../../Public/' . $config['foto_perfil'];
            if (file_exists($rutaCompleta)) {
                @unlink($rutaCompleta);
            }
            
            // Actualizar BD
            $stmt = $this->db->prepare("UPDATE configuracion_usuario SET foto_perfil = NULL WHERE id_usuario = ?");
            return $stmt->execute([$id_usuario]);
        }
        
        return true;
    }

    /**
     * Obtener datos completos del usuario con configuración
     */
    public function obtenerPerfilCompleto(int $id_usuario): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, c.foto_perfil, c.bio, c.fecha_actualizacion
            FROM usuarios u
            LEFT JOIN configuracion_usuario c ON u.id_usuario = c.id_usuario
            WHERE u.id_usuario = ? AND u.activo = 1
        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
