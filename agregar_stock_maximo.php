<?php
require 'app/config/conexion.php';

try {
    // Verificar si la columna ya existe
    $stmt = $conexion->query("SHOW COLUMNS FROM equipos LIKE 'stock_maximo'");
    
    if ($stmt->rowCount() == 0) {
        echo "Agregando columna stock_maximo...\n";
        
        // Agregar columna
        $conexion->exec("ALTER TABLE equipos ADD COLUMN stock_maximo INT NOT NULL DEFAULT 0 AFTER stock");
        echo "✅ Columna agregada.\n";
        
        // Sincronizar: establecer stock_maximo igual al stock actual
        $conexion->exec("UPDATE equipos SET stock_maximo = stock WHERE stock_maximo = 0");
        echo "✅ Stock máximo sincronizado con stock actual.\n";
        
        // Mostrar equipos actualizados
        $result = $conexion->query("SELECT id_equipo, nombre_equipo, stock, stock_maximo FROM equipos");
        echo "\n=== EQUIPOS ACTUALIZADOS ===\n";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: {$row['id_equipo']} | {$row['nombre_equipo']} | Stock actual: {$row['stock']} | Stock máximo: {$row['stock_maximo']}\n";
        }
    } else {
        echo "ℹ️ La columna stock_maximo ya existe.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
