<?php
/**
 * Script para actualizar URLs de notificaciones antiguas
 * Convierte URLs viejas a URLs que redirijan a la página de Notificaciones
 * Ejecutar una sola vez
 */

require_once 'app/config/conexion.php';

try {
    echo "<h3>Actualizando URLs de notificaciones...</h3>";
    
    // Actualizar notificaciones que apuntan a Historial.php
    $sql1 = "UPDATE notificaciones 
             SET url = CASE 
                WHEN url LIKE '%Historial.php%' THEN 'Profesor.php?view=notificaciones'
                ELSE url
             END
             WHERE url LIKE '%Historial.php%'";
    $result1 = $conexion->exec($sql1);
    echo "<p style='color: green;'>✓ Actualizadas {$result1} notificaciones de Historial.php</p>";
    
    // Actualizar notificaciones que apuntan directamente a historial_global sin Admin.php
    $sql2 = "UPDATE notificaciones 
             SET url = 'Admin.php?view=notificaciones'
             WHERE url = '?view=historial_global' 
             OR url LIKE '%?view=historial_global%'";
    $result2 = $conexion->exec($sql2);
    echo "<p style='color: green;'>✓ Actualizadas {$result2} notificaciones de Admin</p>";
    
    // Actualizar notificaciones que apuntan directamente a historial sin Encargado.php
    $sql3 = "UPDATE notificaciones 
             SET url = 'Encargado.php?view=notificaciones'
             WHERE url = '?view=historial' 
             OR url = '?view=devolucion'";
    $result3 = $conexion->exec($sql3);
    echo "<p style='color: green;'>✓ Actualizadas {$result3} notificaciones de Encargado</p>";
    
    echo "<h3>Proceso completado ✓</h3>";
    echo "<p><strong>Total de notificaciones actualizadas: " . ($result1 + $result2 + $result3) . "</strong></p>";
    echo "<p><a href='app/view/Admin.php'>Ir al panel de administración</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
