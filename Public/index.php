<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h4 class="text-center mb-4">Iniciar Sesión</h4>
        <form action="procesar_login.php" method="POST">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
            </div>
            <div class="mb-3">
                <label for="contraseña" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contraseña" name="contraseña" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
        <p class="text-center mt-3 mb-0">
            <a href="#">¿Olvidaste tu contraseña?</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>