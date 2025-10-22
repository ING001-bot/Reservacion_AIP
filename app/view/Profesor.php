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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/theme.js"></script>
<script>
// Salvaguarda global: si por alguna razón la vista no disparó OTP, lo hacemos aquí.
(function(){
  <?php if (($_SESSION['tipo'] ?? '') === 'Profesor'): ?>
  const params = new URLSearchParams(location.search);
  const view = (params.get('view')||'inicio').toLowerCase();
  const requiereOtp = ['prestamo','reserva','password'];
  if (requiereOtp.includes(view)) {
    if (window.__otpInit) return; // evitar duplicados si la vista ya lo hace
    window.__otpInit = true;
    (async function(){
      try{
        let resp = await fetch('../api/otp_send.php?purpose=prestamo', { method:'POST' });
        let data = await resp.json();
        if (!resp.ok || !data.ok) {
          const msg = data.msg || ('HTTP '+resp.status);
          if (/verificar tu teléfono/i.test(msg)) {
            let r2 = await fetch('../api/otp_send.php?purpose=phone_verify', { method:'POST' });
            let d2 = await r2.json();
            if (!r2.ok || !d2.ok) throw new Error(d2.msg||('HTTP '+r2.status));
            const ask = await Swal.fire({ title:'Ingresa el código', input:'text', inputLabel:'Código de 6 dígitos', inputPlaceholder:'######', inputAttributes:{ maxlength:6, autocapitalize:'off', autocorrect:'off' }, confirmButtonText:'Verificar', allowOutsideClick:false, allowEscapeKey:false });
            if (!ask.value || !/^\d{6}$/.test(String(ask.value))) throw new Error('Código inválido.');
            const fdv = new FormData(); fdv.append('code', ask.value); fdv.append('purpose','phone_verify');
            let v2 = await fetch('../api/otp_verify.php', { method:'POST', body: fdv });
            let j2 = await v2.json();
            if (!v2.ok || !j2.ok) throw new Error(j2.msg||('HTTP '+v2.status));
            await Swal.fire({ icon:'success', title:'Teléfono verificado' });
            resp = await fetch('../api/otp_send.php?purpose=prestamo', { method: 'POST' });
            data = await resp.json();
            if (!resp.ok || !data.ok) throw new Error(data.msg||('HTTP '+resp.status));
          } else {
            throw new Error(msg);
          }
        }
        const askCode = await Swal.fire({ title:'Verificación 2FA', input:'text', inputLabel:'Te enviamos un código de 6 dígitos a tu teléfono', inputPlaceholder:'######', inputAttributes:{ maxlength:6, autocapitalize:'off', autocorrect:'off' }, confirmButtonText:'Verificar', allowOutsideClick:false, allowEscapeKey:false });
        if (!askCode.value || !/^\d{6}$/.test(String(askCode.value))) throw new Error('Código inválido.');
        const fd = new FormData(); fd.append('code', askCode.value); fd.append('purpose','prestamo');
        const vr = await fetch('../api/otp_verify.php', { method:'POST', body: fd });
        const vj = await vr.json();
        if (!vr.ok || !vj.ok) throw new Error(vj.msg||('HTTP '+vr.status));
        // éxito: no redirigimos, la vista sigue funcionando y el servidor validará al enviar
      }catch(err){
        console.warn('OTP global no pudo iniciar:', err);
      }
    })();
  }
  <?php endif; ?>
})();
</script>
</body>
</html>