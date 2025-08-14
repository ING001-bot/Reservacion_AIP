<?php
require 'app/config/conexion.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function buscarPorCorreo($correo) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch();
    }

    public function registrar($nombre, $correo, $contraseña, $tipo_usuario) {
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$nombre, $correo, $contraseña, $tipo_usuario]);
    }

    public function obtenerPorCorreo($correo) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarContraseña($hash, $correo) {
        $stmt = $this->db->prepare("UPDATE usuarios SET contraseña = ? WHERE correo = ?");
        return $stmt->execute([$hash, $correo]);
    }
    public function existeCorreo($correo) {
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
    return $stmt->rowCount() > 0;
    }
}
