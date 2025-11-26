<?php
/**
 * Script para agregar columna metadata a la tabla notificaciones
 * Ejecutar una sola vez
 */

require_once 'app/config/conexion.php';

try {
    echo "<h3>Agregando columna metadata a notificaciones...</h3>";
    
    // Verificar si la columna ya existe
    $check = $conexion->query("SHOW COLUMNS FROM notificaciones LIKE 'metadata'");
    if ($check->rowCount() > 0) {
        echo "<p style='color: orange;'>✓ La columna 'metadata' ya existe en la tabla notificaciones.</p>";
    } else {
        // Agregar columna metadata
        $sql = "ALTER TABLE notificaciones 
                ADD COLUMN metadata JSON NULL AFTER url";
        $conexion->exec($sql);
        echo "<p style='color: green;'>✓ Columna 'metadata' agregada exitosamente.</p>";
    }
    
    echo "<h3>Proceso completado ✓</h3>";
    echo "<p><a href='app/view/Admin.php'>Ir al panel de administración</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
