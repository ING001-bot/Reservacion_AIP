<?php
// app/view/dashboard_encargado.php
session_start();
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php'); exit;
}
if ($_SESSION['tipo'] !== 'Encargado') {
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
  <title>Encargado - <?= $usuario ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<!-- Offcanvas lateral -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
  <div class="offcanvas-header bg-brand text-white">
    <h5 class="offcanvas-title" id="offcanvasMenuLabel">Menú</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <a class="nav-link mb-2" href="?view=historial">📄 Historial / PDF</a>
    <a class="nav-link mb-2" href="?view=devolucion">🔄 Registrar Devolución</a>
    <div class="mt-auto">
      <a class="nav-link text-danger" href="../controllers/LogoutController.php">🚪 Cerrar sesión</a>
    </div>
  </div>
</div>

<!-- Contenido dinámico -->
<main class="container py-5">
  <?php
  switch ($vista) {
      case 'historial':
          // Mostrar el Historial Global también para Encargado
          include 'HistorialGlobal.php';
          break;
      case 'devolucion':
          include 'devolucion.php';
          break;
      default: ?>
          <div class="text-center mb-4">
              <h1 class="fw-bold text-brand">🧰 Panel del Encargado</h1>
              <p class="text-muted">Gestione devoluciones y consulte historiales.</p>
          </div>
          <div class="row g-4 justify-content-center">
              <div class="col-sm-6 col-md-5">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Historial</h5>
                          <p class="card-text text-muted mb-4">Reservas y préstamos del sistema.</p>
                          <a href="?view=historial" class="btn btn-outline-brand mt-auto">Abrir</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-5">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">Devoluciones</h5>
                          <p class="card-text text-muted mb-4">Registrar devoluciones de equipos.</p>
                          <a href="?view=devolucion" class="btn btn-outline-brand mt-auto">Abrir</a>
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
</body>
</html>
