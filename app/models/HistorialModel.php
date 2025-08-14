<?php
require 'app/config/conexion.php';

class HistorialModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function obtenerDatosUsuario($id_usuario) {
        $stmt = $this->db->prepare("SELECT nombre, correo FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerReservas($id_usuario) {
        $stmt = $this->db->prepare("
            SELECT a.nombre_aula, r.fecha, r.hora_inicio, r.hora_fin
            FROM reservas r
            JOIN aulas a ON r.id_aula = a.id_aula
            WHERE r.id_usuario = ?
            ORDER BY r.fecha DESC
        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPrestamos($id_usuario) {
        $stmt = $this->db->prepare("
            SELECT e.nombre_equipo, p.fecha_prestamo, p.fecha_devolucion, p.estado
            FROM prestamos p
            JOIN equipos e ON p.id_equipo = e.id_equipo
            WHERE p.id_usuario = ?
            ORDER BY p.fecha_prestamo DESC
        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
