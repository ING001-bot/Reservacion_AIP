<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuarioModel {
    private $db;

    /**
     * @param PDO|null $conexion Conexión a la base de datos. Si es null, se usará la conexión global.
     */
    public function __construct($conexion = null) {
        if ($conexion === null) {
            global $conexion; // Usar la conexión global si no se proporciona una
        }
        $this->db = $conexion;
        $this->ensureSchema();
    }

    public function existeCorreo($correo) {
        $correo = strtolower(trim($correo));
        // Solo considera usuarios activos para bloquear registros
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ? AND activo = 1");
        $stmt->execute([$correo]);
        return $stmt->rowCount() > 0;
    }

    // Verifica si el correo ya está usado por otro usuario activo distinto al dado
    public function existeCorreoDeOtro(string $correo, int $id_usuario): bool {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ? AND activo = 1 AND id_usuario <> ?");
        $stmt->execute([$correo, $id_usuario]);
        return $stmt->rowCount() > 0;
    }

    public function existeCorreoInactivo($correo) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ? AND activo = 0");
        $stmt->execute([$correo]);
        return $stmt->rowCount() > 0;
    }

    // Verifica si el teléfono ya está usado por un usuario activo
    public function existeTelefono(?string $telefono): bool {
        if ($telefono === null || $telefono === '') return false;
        $tel = $this->normalizePhone($telefono);
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE telefono = ?");
        $stmt->execute([$tel]);
        return $stmt->rowCount() > 0;
    }

    // Verifica si el teléfono ya está usado por otro usuario activo
    public function existeTelefonoDeOtro(?string $telefono, int $id_usuario): bool {
        if ($telefono === null || $telefono === '') return false;
        $tel = $this->normalizePhone($telefono);
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE telefono = ? AND id_usuario <> ?");
        $stmt->execute([$tel, $id_usuario]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Obtiene un usuario por su ID
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o false si no se encuentra
     */
    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND activo = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrar($nombre, $correo, $contraseña, $tipo_usuario, ?string $telefono = null) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $telefono = $this->normalizePhone($telefono);
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, telefono) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario, $telefono]);
    }

    // Registrar con verificación por correo (verificado=0 y token)
    public function registrarConVerificacion($nombre, $correo, $contraseña, $tipo_usuario, $verification_token, $token_expira) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, verificado, verification_token, token_expira) VALUES (?, ?, ?, ?, 0, ?, ?)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario, $verification_token, $token_expira]);
    }

    // Registrar con verificado=1 (para cuentas creadas por administrador)
    public function registrarVerificado($nombre, $correo, $contraseña, $tipo_usuario, ?string $telefono = null) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $telefono = $this->normalizePhone($telefono);
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, telefono, verificado, verification_token, token_expira) VALUES (?, ?, ?, ?, ?, 1, NULL, NULL)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario, $telefono]);
    }

    // Reactivar (reusar fila existente por UNIQUE correo) para admin, PERO exigiendo verificación por correo
    public function reactivarUsuarioAdmin($nombre, $correo, $contraseña, $tipo_usuario, string $token, string $expira) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, contraseña = ?, tipo_usuario = ?, verificado = 0, verification_token = ?, token_expira = ?, activo = 1 WHERE correo = ?");
        return $stmt->execute([$nombre, $contraseña, $tipo_usuario, $token, $expira, $correo]);
    }

    public function obtenerUsuarios() {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario, telefono FROM usuarios WHERE activo = 1");
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario, telefono, telefono_verificado FROM usuarios WHERE activo = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarUsuario($id_usuario) {
        // Baja lógica para evitar violación de FK (reservas, préstamos, etc.)
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?");
        return $stmt->execute([$id_usuario]);
    }

    public function eliminarPorCorreo($correo) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE correo = ?");
        return $stmt->execute([$correo]);
    }

    public function actualizarUsuario($id_usuario, $nombre, $correo, $tipo_usuario, ?string $telefono = null) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $telefono = $this->normalizePhone($telefono);
        // Si cambia el teléfono, reiniciar verificación
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, correo = ?, tipo_usuario = ?, telefono = ?, telefono_verificado = IF(telefono <> ?, 0, telefono_verificado), telefono_verificado_at = IF(telefono <> ?, NULL, telefono_verificado_at) WHERE id_usuario = ?");
        return $stmt->execute([$nombre, $correo, $tipo_usuario, $telefono, $telefono, $telefono, $id_usuario]);
    }

    // Actualiza correo estableciendo verificado=0 y guardando token/expira
    public function actualizarCorreoConVerificacion(int $id_usuario, string $nombre, string $nuevoCorreo, string $tipo_usuario, ?string $telefono, string $token, string $expira): bool {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($nuevoCorreo));
        $telefono = $this->normalizePhone($telefono);
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, tipo_usuario = ?, telefono = ?, verificado = 0, verification_token = ?, token_expira = ? WHERE id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $correo, $tipo_usuario, $telefono, $token, $expira, $id_usuario]);
    }

    // Actualiza el teléfono por correo (útil inmediatamente después de crear)
    public function actualizarTelefonoPorCorreo(string $correo, ?string $telefono): bool {
        $correo = strtolower(trim($correo));
        $tel = $telefono !== null ? trim($telefono) : null;
        $stmt = $this->db->prepare("UPDATE usuarios SET telefono = :t WHERE correo = :c");
        $stmt->bindValue(':t', $tel, $tel === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
        $stmt->bindValue(':c', $correo, \PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Actualiza el teléfono por ID (para edición)
    public function actualizarTelefonoPorId(int $id_usuario, ?string $telefono): bool {
        $tel = $telefono !== null ? trim($telefono) : null;
        $stmt = $this->db->prepare("UPDATE usuarios SET telefono = :t WHERE id_usuario = :id");
        $stmt->bindValue(':t', $tel, $tel === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
        $stmt->bindValue(':id', $id_usuario, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerPorCorreo($correo) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, telefono, telefono_verificado, contraseña, tipo_usuario, verificado, activo FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

     // Alias usado por algunos controladores existentes
     public function buscarPorCorreo($correo) {
         return $this->obtenerPorCorreo($correo);
     }

    public function actualizarContraseña($nuevaContraseña, $correo) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET contraseña = ? WHERE correo = ?");
        return $stmt->execute([$nuevaContraseña, $correo]);
    }

    public function actualizarContraseñaPorId($id_usuario, $nuevaContraseña) {
        $stmt = $this->db->prepare("UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?");
        return $stmt->execute([$nuevaContraseña, $id_usuario]);
    }

    public function actualizarVerificacionPorToken($correo, $token) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET verificado = 1, verification_token = NULL, token_expira = NULL WHERE correo = ? AND verification_token = ? AND (token_expira IS NULL OR token_expira >= NOW())");
        $stmt->execute([$correo, $token]);
        return $stmt->rowCount() > 0;
    }

    // Reactivar (reusar fila) para registro público (verificado=0 y set token/expira, tipo Profesor, activo=1)
    public function reactivarUsuarioPublico($nombre, $correo, $contraseña, $token, $expira) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, contraseña = ?, tipo_usuario = 'Profesor', verificado = 0, verification_token = ?, token_expira = ?, activo = 1 WHERE correo = ?");
        return $stmt->execute([$nombre, $contraseña, $token, $expira, $correo]);
    }

    public function guardarResetToken($correo, $token, $expira) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE correo = ?");
        return $stmt->execute([$token, $expira, $correo]);
    }

    public function obtenerPorResetToken($token) {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo FROM usuarios WHERE reset_token = ? AND (reset_expira IS NULL OR reset_expira >= NOW())");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function limpiarResetToken($correo) {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = NULL, reset_expira = NULL WHERE correo = ?");
        return $stmt->execute([$correo]);
    }

    // -------------------- Magic login (enlace de un solo uso) --------------------
    public function guardarLoginToken(string $correo, string $token, string $expira): bool {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET login_token = ?, login_expira = ? WHERE correo = ?");
        return $stmt->execute([$token, $expira, $correo]);
    }

    public function obtenerPorLoginToken(string $token): ?array {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario FROM usuarios WHERE login_token = ? AND (login_expira IS NULL OR login_expira >= NOW())");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function limpiarLoginToken(string $correo): bool {
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET login_token = NULL, login_expira = NULL WHERE correo = ?");
        return $stmt->execute([$correo]);
    }

    private function ensureSchema(): void {
        try {
            // agregar columna telefono si no existe
            $this->db->exec("CREATE TABLE IF NOT EXISTS otp_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                purpose VARCHAR(32) NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                attempts TINYINT NOT NULL DEFAULT 0,
                sent_at DATETIME NOT NULL,
                INDEX idx_user_purpose (id_usuario, purpose),
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // add column telefono if missing
            $stmt = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'telefono'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(32) NULL AFTER correo");
            }
            // add telefono_verificado columns if missing
            $stmt = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'telefono_verificado'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE usuarios ADD COLUMN telefono_verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER telefono");
            }
            $stmt = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'telefono_verificado_at'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE usuarios ADD COLUMN telefono_verificado_at DATETIME NULL AFTER telefono_verificado");
            }
        } catch (\Throwable $e) {
            error_log('ensureSchema UsuarioModel: '.$e->getMessage());
        }
    }

    private function normalizePhone(?string $telefono): ?string {
        if (!$telefono) return null;
        $t = trim($telefono);
        // mantener solo dígitos y quedarnos con los últimos 9
        $digits = preg_replace('/\D+/', '', $t) ?? '';
        if (strlen($digits) >= 9) {
            return substr($digits, -9);
        }
        return null;
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function obtenerEstadisticas(): array {
        $stats = [];
        
        // Total de usuarios activos
        $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1");
        $stats['total'] = (int)$stmt->fetchColumn();
        
        // Por tipo
        $stmt = $this->db->query("SELECT tipo_usuario, COUNT(*) as cantidad FROM usuarios WHERE activo = 1 GROUP BY tipo_usuario");
        $porTipo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $stats['administradores'] = 0;
        $stats['encargados'] = 0;
        $stats['profesores'] = 0;
        
        foreach ($porTipo as $row) {
            switch ($row['tipo_usuario']) {
                case 'Administrador':
                    $stats['administradores'] = (int)$row['cantidad'];
                    break;
                case 'Encargado':
                    $stats['encargados'] = (int)$row['cantidad'];
                    break;
                case 'Profesor':
                    $stats['profesores'] = (int)$row['cantidad'];
                    break;
            }
        }
        
        // Usuarios verificados
        $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND verificado = 1");
        $stats['verificados'] = (int)$stmt->fetchColumn();
        
        // Usuarios con teléfono verificado
        $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND telefono_verificado = 1");
        $stats['telefono_verificado'] = (int)$stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Obtener lista de usuarios por tipo
     */
    public function obtenerUsuariosPorTipo(string $tipo): array {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, telefono, verificado, telefono_verificado FROM usuarios WHERE tipo_usuario = ? AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si un usuario es administrador
     */
    public function esAdministrador(int $id_usuario): bool {
        $stmt = $this->db->prepare("SELECT tipo_usuario FROM usuarios WHERE id_usuario = ? AND activo = 1");
        $stmt->execute([$id_usuario]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row && $row['tipo_usuario'] === 'Administrador';
    }
    
    /**
     * Contar administradores activos
     */
    public function contarAdministradores(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'Administrador' AND activo = 1");
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Verificar si se puede eliminar un usuario
     * No se puede eliminar si es el último administrador
     */
    public function puedeEliminar(int $id_usuario): array {
        // Verificar si es administrador
        if (!$this->esAdministrador($id_usuario)) {
            return ['puede' => true];
        }
        
        // Si es administrador, verificar que no sea el último
        $totalAdmins = $this->contarAdministradores();
        
        if ($totalAdmins <= 1) {
            return [
                'puede' => false,
                'razon' => '⚠️ No se puede eliminar el último administrador del sistema'
            ];
        }
        
        return ['puede' => true];
    }
}
?>
