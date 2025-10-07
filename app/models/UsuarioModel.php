<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function existeCorreo($correo) {
        // Solo considera usuarios activos para bloquear registros
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ? AND activo = 1");
        $stmt->execute([$correo]);
        return $stmt->rowCount() > 0;
    }

    // Verifica si el correo ya está usado por otro usuario activo distinto al dado
    public function existeCorreoDeOtro(string $correo, int $id_usuario): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ? AND activo = 1 AND id_usuario <> ?");
        $stmt->execute([$correo, $id_usuario]);
        return $stmt->rowCount() > 0;
    }

    public function existeCorreoInactivo($correo) {
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ? AND activo = 0");
        $stmt->execute([$correo]);
        return $stmt->rowCount() > 0;
    }

    public function registrar($nombre, $correo, $contraseña, $tipo_usuario) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario]);
    }

    // Registrar con verificación por correo (verificado=0 y token)
    public function registrarConVerificacion($nombre, $correo, $contraseña, $tipo_usuario, $verification_token, $token_expira) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, verificado, verification_token, token_expira) VALUES (?, ?, ?, ?, 0, ?, ?)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario, $verification_token, $token_expira]);
    }

    // Registrar con verificado=1 (para cuentas creadas por administrador)
    public function registrarVerificado($nombre, $correo, $contraseña, $tipo_usuario) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario, verificado, verification_token, token_expira) VALUES (?, ?, ?, ?, 1, NULL, NULL)");
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario]);
    }

    // Reactivar (reusar fila existente por UNIQUE correo) para admin
    public function reactivarUsuarioAdmin($nombre, $correo, $contraseña, $tipo_usuario) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, contraseña = ?, tipo_usuario = ?, verificado = 1, verification_token = NULL, token_expira = NULL, activo = 1 WHERE correo = ?");
        return $stmt->execute([$nombre, $contraseña, $tipo_usuario, $correo]);
    }

    public function obtenerUsuarios() {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario FROM usuarios WHERE activo = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarUsuario($id_usuario) {
        // Baja lógica para evitar violación de FK (reservas, préstamos, etc.)
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?");
        return $stmt->execute([$id_usuario]);
    }

    public function eliminarPorCorreo($correo) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE correo = ?");
        return $stmt->execute([$correo]);
    }

    public function actualizarUsuario($id_usuario, $nombre, $correo, $tipo_usuario) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, correo = ?, tipo_usuario = ? WHERE id_usuario = ?");
        return $stmt->execute([$nombre, $correo, $tipo_usuario, $id_usuario]);
    }

    public function obtenerPorCorreo($correo) {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, contraseña, tipo_usuario, verificado, activo FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

     // Alias usado por algunos controladores existentes
     public function buscarPorCorreo($correo) {
         return $this->obtenerPorCorreo($correo);
     }

    public function actualizarContraseña($nuevaContraseña, $correo) {
        $stmt = $this->db->prepare("UPDATE usuarios SET contraseña = ? WHERE correo = ?");
        return $stmt->execute([$nuevaContraseña, $correo]);
    }

    public function actualizarContraseñaPorId($id_usuario, $nuevaContraseña) {
        $stmt = $this->db->prepare("UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?");
        return $stmt->execute([$nuevaContraseña, $id_usuario]);
    }

    public function actualizarVerificacionPorToken($correo, $token) {
        $stmt = $this->db->prepare("UPDATE usuarios SET verificado = 1, verification_token = NULL, token_expira = NULL WHERE correo = ? AND verification_token = ? AND (token_expira IS NULL OR token_expira >= NOW())");
        $stmt->execute([$correo, $token]);
        return $stmt->rowCount() > 0;
    }

    // Reactivar (reusar fila) para registro público (verificado=0 y set token/expira, tipo Profesor, activo=1)
    public function reactivarUsuarioPublico($nombre, $correo, $contraseña, $token, $expira) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, contraseña = ?, tipo_usuario = 'Profesor', verificado = 0, verification_token = ?, token_expira = ?, activo = 1 WHERE correo = ?");
        return $stmt->execute([$nombre, $contraseña, $token, $expira, $correo]);
    }

    public function guardarResetToken($correo, $token, $expira) {
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE correo = ?");
        return $stmt->execute([$token, $expira, $correo]);
    }

    public function obtenerPorResetToken($token) {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo FROM usuarios WHERE reset_token = ? AND (reset_expira IS NULL OR reset_expira >= NOW())");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function limpiarResetToken($correo) {
        $stmt = $this->db->prepare("UPDATE usuarios SET reset_token = NULL, reset_expira = NULL WHERE correo = ?");
        return $stmt->execute([$correo]);
    }

    // -------------------- Magic login (enlace de un solo uso) --------------------
    public function guardarLoginToken(string $correo, string $token, string $expira): bool {
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
        $stmt = $this->db->prepare("UPDATE usuarios SET login_token = NULL, login_expira = NULL WHERE correo = ?");
        return $stmt->execute([$correo]);
    }
}
?>
