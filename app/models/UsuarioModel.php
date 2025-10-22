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

    public function registrar($nombre, $correo, $contraseña, $tipo_usuario) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario]);
    }

    // Registrar con verificación por correo (verificado=0 y token)
    public function registrarConVerificacion($nombre, $correo, $contraseña, $tipo_usuario, $verification_token, $token_expira) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, verificado, verification_token, token_expira) VALUES (?, ?, ?, ?, 0, ?, ?)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario, $verification_token, $token_expira]);
    }

    // Registrar con verificado=1 (para cuentas creadas por administrador)
    public function registrarVerificado($nombre, $correo, $contraseña, $tipo_usuario) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, verificado, verification_token, token_expira) VALUES (?, ?, ?, ?, 1, NULL, NULL)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario]);
    }

    // Reactivar (reusar fila existente por UNIQUE correo) para admin
    public function reactivarUsuarioAdmin($nombre, $correo, $contraseña, $tipo_usuario) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, contraseña = ?, tipo_usuario = ?, verificado = 1, verification_token = NULL, token_expira = NULL, activo = 1 WHERE correo = ?");
        return $stmt->execute([$nombre, $contraseña, $tipo_usuario, $correo]);
    }

    public function obtenerUsuarios() {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario, telefono FROM usuarios WHERE activo = 1");
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

    public function actualizarUsuario($id_usuario, $nombre, $correo, $tipo_usuario) {
        $nombre = ucwords(strtolower(trim($nombre)));
        $correo = strtolower(trim($correo));
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, correo = ?, tipo_usuario = ? WHERE id_usuario = ?");
        return $stmt->execute([$nombre, $correo, $tipo_usuario, $id_usuario]);
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
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, contraseña, tipo_usuario, verificado, activo FROM usuarios WHERE correo = ?");
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
}
?>
