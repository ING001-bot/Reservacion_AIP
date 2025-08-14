<?php
$host    = "localhost";
$user    = "root";
$pass    = "";
$db      = "aula_innovacion";
$charset = "utf8mb4";

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      
    PDO::ATTR_EMULATE_PREPARES   => false,                 
];

try {
    $conexion = new PDO($dsn, $user, $pass, $options);

    // Opcional: puedes activar esta línea para confirmar conexión en pruebas
    // echo "✅ Conexión exitosa a la base de datos.";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), '2002') !== false) {
        die("⚠ No se pudo conectar a MySQL. Verifica si XAMPP está encendido y MySQL activo.");
    } elseif (strpos($e->getMessage(), '1049') !== false) {
        die("⚠ La base de datos '{$db}' no existe.");
    } elseif (strpos($e->getMessage(), '1045') !== false) {
        die("⚠ Usuario o contraseña de MySQL incorrectos.");
    } else {
        die("❌ Error de conexión: " . $e->getMessage());
    }
}
?>
