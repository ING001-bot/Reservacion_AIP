<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$nombre = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario');
$tipo = htmlspecialchars($_SESSION['tipo'] ?? '');
require_once __DIR__ . '/../../controllers/PrestamoController.php';
require_once __DIR__ . '/../../config/conexion.php';
$pc = new PrestamoController($conexion);
$notis = $id_usuario ? $pc->listarNotificacionesUsuario($id_usuario, true, 10) : [];
$no_leidas = array_values(array_filter($notis, function($n){ return (int)$n['leida'] === 0; })) ;
$badge = count($no_leidas);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-brand shadow-none mb-0" style="border-bottom:0;">
  <div class="container-fluid">
    <a class="navbar-brand text-brand" href="#">AIP</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <li class="nav-item dropdown">
          <a class="nav-link position-relative text-white" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            🔔
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" id="notif-count" style="display: <?= $badge? 'inline':'none' ?>;">
              <?= $badge ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0 shadow" aria-labelledby="notifDropdown" style="min-width: 320px;">
            <div class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
              <strong>Notificaciones</strong>
              <button class="btn btn-sm btn-outline-secondary rounded-pill" id="notif-markall">Marcar todas</button>
            </div>
            <div class="list-group list-group-flush" id="notif-list">
              <?php if (empty($notis)): ?>
                <div class="p-3 text-muted small">Sin notificaciones nuevas.</div>
              <?php else: ?>
                <?php foreach ($notis as $n): ?>
                  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start" href="<?= htmlspecialchars($n['url'] ?? '#') ?>">
                    <div>
                      <div class="fw-semibold small"><?= htmlspecialchars($n['titulo']) ?></div>
                      <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($n['mensaje'], 0, 80, '…')) ?></div>
                    </div>
                    <?php if (!(int)$n['leida']): ?>
                      <span class="badge bg-primary rounded-pill">nuevo</span>
                    <?php endif; ?>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </li>
        <li class="nav-item d-none d-lg-block">
          <span class="nav-link">👤 <?= $nombre ?><?= $tipo? ' · '.$tipo : '' ?></span>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="../controllers/LogoutController.php" title="Cerrar sesión">
            <span class="d-inline d-lg-none">🚪</span>
            <span class="d-none d-lg-inline">🚪 Cerrar sesión</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<script src="../../Public/js/notifications.js"></script>
