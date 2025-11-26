<?php
require 'app/config/conexion.php';

$stmt = $conexion->query('SELECT id_notificacion, titulo, url FROM notificaciones ORDER BY id_notificacion DESC LIMIT 10');
echo "=== ÚLTIMAS NOTIFICACIONES ===\n\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id_notificacion'] . "\n";
    echo "Título: " . $row['titulo'] . "\n";
    echo "URL: [" . $row['url'] . "]\n";
    echo "URL procesada: /Reservacion_AIP/app/view/" . $row['url'] . "\n";
    echo "---\n";
}
