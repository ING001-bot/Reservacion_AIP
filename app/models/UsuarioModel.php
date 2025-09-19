<?php
require '../config/conexion.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function existeCorreo($correo) {
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ?");
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
}
?>
