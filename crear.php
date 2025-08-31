<?php
// Configura tus datos de conexión
$host = 'localhost';
$db   = 'aula_innovacion';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Conectar a la base de datos
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE tipo_usuario = 'Administrador' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        echo "⚠️ Ya existe un usuario administrador.\n";
    } else {
        $nombre = 'Admin';
        $correo = 'admin@correo.com';
        $passwordPlano = '123456'; 
        $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
        $tipo = 'Administrador';

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contraseña, tipo_usuario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $correo, $hash, $tipo]);

        echo "✅ Usuario administrador creado con éxito.\n";
        echo "📧 Correo: $correo\n";
        echo "🔑 Contraseña: $passwordPlano\n";
    }

} catch (PDOException $e) {
    echo "❌ Error de conexión o ejecución: " . $e->getMessage();
}
?>
