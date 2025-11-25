<?php
// app/api/check_session.php
// Endpoint para verificar si la sesión está activa (sin generar HTML)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers para evitar caché
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Verificar si hay sesión activa
$isLoggedIn = isset($_SESSION['usuario']) && isset($_SESSION['tipo']);

// Devolver respuesta JSON
echo json_encode([
    'logged_in' => $isLoggedIn,
    'user' => $isLoggedIn ? $_SESSION['usuario'] : null,
    'role' => $isLoggedIn ? $_SESSION['tipo'] : null
]);
exit;
