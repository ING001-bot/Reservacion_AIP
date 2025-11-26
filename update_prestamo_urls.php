<?php
require 'app/config/conexion.php';

// Actualizar notificaciones de préstamo existentes
$stmt = $conexion->prepare("UPDATE notificaciones SET url = 'Historial.php#equipos' WHERE titulo LIKE '%Préstamo%' AND url NOT LIKE '%#equipos%'");
$stmt->execute();
echo "Notificaciones de préstamo actualizadas: " . $stmt->rowCount() . "\n";

// Verificar
$stmt2 = $conexion->query("SELECT id_notificacion, titulo, url FROM notificaciones WHERE titulo LIKE '%Préstamo%' ORDER BY id_notificacion DESC LIMIT 5");
echo "\n=== NOTIFICACIONES DE PRÉSTAMO ACTUALIZADAS ===\n";
while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id_notificacion'] . " | Título: " . $row['titulo'] . " | URL: " . $row['url'] . "\n";
}
