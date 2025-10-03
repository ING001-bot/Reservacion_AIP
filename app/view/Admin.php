<?php
// app/view/dashboard_admin.php
session_start();
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php'); exit;
}
if ($_SESSION['tipo'] !== 'Administrador') {
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
  <title>Administrador - <?= $usuario ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Estilos de marca -->
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <style>
    /* Para que sidebar quede fijo en escritorio */
    @media (min-width: 992px) {
      .offcanvas-lg {
        position: static !important;
        transform: none !important;
        visibility: visible !important;
        border-right: 1px solid rgba(255,255,255,.2);
      }
    }
  </style>
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<!-- Barra superior (solo visible en móvil) -->
<nav class="navbar navbar-dark bg-brand d-lg-none">
  <div class="container-fluid">
    <button class="btn btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#sidebarAdmin">
      ☰ Menú
    </button>
    <span class="navbar-text text-white ms-auto">
      <?= $usuario ?> (Admin)
    </span>
  </div>
</nav>

<div class="d-flex">
  <!-- Sidebar (Offcanvas) -->
  <div class="offcanvas offcanvas-start bg-brand text-white offcanvas-lg" tabindex="-1" id="sidebarAdmin">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title">Menú Admin</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
      <div class="d-flex align-items-center mb-3">
        <img src="../../Public/img/logo_colegio.png" alt="Logo" style="height:40px;" class="me-2">
        <strong>Colegio MJS</strong>
      </div>
      <hr class="border-light opacity-50">
      <nav class="nav flex-column gap-1">
        <a class="nav-link link-sidebar <?= $vista==='inicio'?'active':'' ?>" href="Admin.php?view=inicio">🏠 Inicio</a>
        <a class="nav-link link-sidebar <?= $vista==='usuarios'?'active':'' ?>" href="Admin.php?view=usuarios">👤 Usuarios</a>
        <a class="nav-link link-sidebar <?= $vista==='aulas'?'active':'' ?>" href="Admin.php?view=aulas">🏫 Aulas</a>
        <a class="nav-link link-sidebar <?= $vista==='equipos'?'active':'' ?>" href="Admin.php?view=equipos">💻 Inventario de Equipos</a>
        <a class="nav-link link-sidebar <?= $vista==='historial_global'?'active':'' ?>" href="Admin.php?view=historial_global">🗂️ Historial General</a>
        <a class="nav-link link-sidebar <?= $vista==='reportes'?'active':'' ?>" href="Admin.php?view=reportes">📊 Reportes / Filtros</a>
        <a class="nav-link link-sidebar <?= $vista==='password'?'active':'' ?>" href="Admin.php?view=password">🔑 Cambiar Contraseña</a>
        <a class="nav-link link-sidebar text-danger" href="../controllers/LogoutController.php">🚪 Cerrar sesión</a>
      </nav>
      <div class="mt-auto small text-white-50">Admin: <?= $usuario ?></div>
    </div>
  </div>

  <!-- Contenido dinámico -->
  <main class="content p-4 flex-grow-1 min-vh-100">
    <?php
    switch ($vista) {
      case 'usuarios':
        include 'Registrar_Usuario.php';
        break;
      case 'aulas':
        include 'Registrar_Aula.php';
        break;
      case 'equipos':
        include 'registrar_equipo.php';
        break;
      case 'historial_global':
        include 'HistorialGlobal.php';
        break;
      case 'reportes':
        include 'HistorialReportes.php';
        break;
      case 'password':
        include 'cambiar_contraseña.php';
        break;
      default: ?>
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <h2 class="mb-3 text-brand">🧑‍💼 Panel de Administración</h2>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="card card-brand shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title">Usuarios</h5>
                <p class="card-text text-muted">Crear, editar y gestionar permisos.</p>
                <a href="dashboard_admin.php?view=usuarios" class="btn btn-outline-brand">Gestionar</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-brand shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title">Aulas</h5>
                <p class="card-text text-muted">Alta y mantenimiento de aulas.</p>
                <a href="dashboard_admin.php?view=aulas" class="btn btn-outline-brand">Gestionar</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-brand shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title">Equipos</h5>
                <p class="card-text text-muted">Inventario y préstamos.</p>
                <a href="dashboard_admin.php?view=equipos" class="btn btn-outline-brand">Gestionar</a>
              </div>
            </div>
          </div>
        </div>
      <?php
    }
    ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
