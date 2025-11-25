<?php
session_start();
session_unset();
session_destroy();

// Prevenir caché del navegador para evitar volver a páginas autenticadas con botón atrás
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Fecha pasada
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando sesión...</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .logout-container {
            text-align: center;
        }
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="spinner"></div>
        <h2>Cerrando sesión...</h2>
        <p>Por favor espere...</p>
    </div>
    
    <script>
        // Marcar que se hizo logout en sessionStorage
        sessionStorage.setItem('logged_out', 'true');
        
        // Limpiar historial para prevenir volver atrás
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', window.location.href);
        }
        
        // Redirigir al login después de un breve momento
        setTimeout(function() {
            window.location.replace('../../Public/index.php');
        }, 500);
    </script>
</body>
</html>
