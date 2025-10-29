<?php
// app/view/dashboard_profesor.php
session_start();
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php'); exit;
}
if ($_SESSION['tipo'] !== 'Profesor') {
    header('Location: Dashboard.php'); exit;
}
$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');

// Determinar qué vista cargar
$vista = $_GET['view'] ?? 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Profesor - <?= $usuario ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<!-- Contenido dinámico -->
<main class="container py-5 content">
  <?php
  switch ($vista) {
      case 'reserva':
          include 'reserva.php';
          break;
      case 'prestamo':
          include 'prestamo.php';
          break;
      case 'historial':
          include 'historial.php';
          break;
      case 'password':
          include 'Cambiar_Contraseña.php';
          break;
      case 'tommibot':
          include 'Tommibot.php';
          break;
      default: ?>
          <div class="text-center mb-4">
              <h1 class="fw-bold text-brand">👨‍🏫 Panel del Profesor</h1>
              <p class="text-muted">Realice rápidamente sus reservas y prestamos.</p>
          </div>
          <div class="row g-4 justify-content-center">
              <div class="col-sm-6 col-md-5">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Reservar Aula</h5>
                          <p class="card-text text-muted mb-4">Consulte disponibilidad y registre su reserva.</p>
                          <a href="?view=reserva" class="btn btn-brand mt-auto">Ir a Reservas</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-5">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Préstamo de Equipos</h5>
                          <p class="card-text text-muted mb-4">Solicite equipos del aula de innovación.</p>
                          <a href="?view=prestamo" class="btn btn-brand mt-auto">Ir a Préstamos</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-5">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Mis Reservas/Préstamos</h5>
                          <p class="card-text text-muted mb-4">Revise su historial y estados.</p>
                          <a href="?view=historial" class="btn btn-outline-brand mt-auto">Ver Historial</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-5">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Cambiar Contraseña</h5>
                          <p class="card-text text-muted mb-4">Actualice su contraseña de acceso.</p>
                          <a href="?view=password" class="btn btn-outline-brand mt-auto">Abrir</a>
                      </div>
                  </div>
              </div>

              
          </div>
  <?php
  }
  ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/theme.js"></script>
<!-- Flujo OTP global eliminado para evitar prompts duplicados; cada vista maneja su modal -->

</body>
</html>