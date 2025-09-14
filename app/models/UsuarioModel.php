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

    public function obtenerUsuarios() {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario FROM usuarios");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarUsuario($id_usuario) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        return $stmt->execute([$id_usuario]);
    }

    public function actualizarUsuario($id_usuario, $nombre, $correo, $tipo_usuario) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = ?, correo = ?, tipo_usuario = ? WHERE id_usuario = ?");
        return $stmt->execute([$nombre, $correo, $tipo_usuario, $id_usuario]);
    }

    public function obtenerPorCorreo($correo) {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, contraseña, tipo_usuario FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarContraseña($nuevaContraseña, $correo) {
        $stmt = $this->db->prepare("UPDATE usuarios SET contraseña = ? WHERE correo = ?");
        return $stmt->execute([$nuevaContraseña, $correo]);
    }
}
?>
