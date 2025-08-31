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
</head>
<body class="bg-light">

<!-- Navbar principal -->
<nav class="navbar navbar-expand-lg navbar-dark bg-brand">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="../../Public/img/logo_colegio.png" alt="Logo" class="me-2" style="height:40px;">
      Colegio Monseñor Juan Tomis Stack
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="d-none d-lg-flex align-items-center">
      <span class="text-white-50 me-3">Profesor: <?= $usuario ?></span>
      <a class="btn btn-outline-light btn-sm" href="../controllers/LogoutController.php">Cerrar sesión</a>
    </div>
  </div>
</nav>

<!-- Offcanvas lateral para móviles -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
  <div class="offcanvas-header bg-brand text-white">
    <h5 class="offcanvas-title" id="offcanvasMenuLabel">Menú</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <a class="nav-link mb-2" href="?view=reserva">📅 Reservar Aula</a>
    <a class="nav-link mb-2" href="?view=prestamo">💻 Préstamo de Equipos</a>
    <a class="nav-link mb-2" href="?view=historial">📄 Mis Reservas/Préstamos</a>
    <div class="mt-auto">
      <a class="nav-link text-danger" href="../controllers/LogoutController.php">🚪 Cerrar sesión</a>
    </div>
  </div>
</div>

<!-- Contenido dinámico -->
<main class="container py-5">
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
      default: ?>
          <div class="text-center mb-4">
              <h1 class="fw-bold text-brand">Panel del Profesor</h1>
              <p class="text-muted">Realice rápidamente sus reservas y prestamos.</p>
          </div>
          <div class="row g-4 justify-content-center">
              <div class="col-sm-6 col-md-5">
                  <div class="card shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Reservar Aula</h5>
                          <p class="card-text text-muted mb-4">Consulte disponibilidad y registre su reserva.</p>
                          <a href="?view=reserva" class="btn btn-brand mt-auto">Ir a Reservas</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-5">
                  <div class="card shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Préstamo de Equipos</h5>
                          <p class="card-text text-muted mb-4">Solicite equipos del aula de innovación.</p>
                          <a href="?view=prestamo" class="btn btn-brand mt-auto">Ir a Préstamos</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-5">
                  <div class="card shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Mis Reservas/Préstamos</h5>
                          <p class="card-text text-muted mb-4">Revise su historial y estados.</p>
                          <a href="?view=historial" class="btn btn-outline-brand mt-auto">Ver Historial</a>
                      </div>
                  </div>
              </div>
          </div>
  <?php
  }
  ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
  