<?php
require 'app/config/conexion.php';

// Corregir stock de laptops
echo "Corrigiendo stock de laptops a 15...\n";
$conexion->exec("UPDATE equipos SET stock = 15, stock_maximo = 15 WHERE id_equipo = 1");
echo "✅ Stock corregido.\n";

// Verificar
$result = $conexion->query("SELECT id_equipo, nombre_equipo, stock, stock_maximo FROM equipos WHERE id_equipo = 1");
$row = $result->fetch(PDO::FETCH_ASSOC);
echo "Laptop Acer: Stock actual = {$row['stock']}, Stock máximo = {$row['stock_maximo']}\n";
