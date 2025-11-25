<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

// Incluir el controlador
require_once __DIR__ . '/../controllers/CambiarContraseñaController.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Aulas de Innovación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../Public/css/cambiar_contraseña.css">
    <style>
        .verification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1100; /* Debajo de la navbar para no bloquear clics */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .code-input-verify {
            letter-spacing: 12px;
            font-size: 2rem;
            text-align: center;
            height: 70px;
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            font-weight: bold;
            margin: 20px 0;
        }
        .code-input-verify:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .error-shake {
            animation: shake 0.5s;
            border-color: #dc3545 !important;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body>

<?php if ($necesitaVerificacion): ?>
<!-- Modal de Verificación -->
<div class="verification-overlay" id="verificationOverlay">
    <div class="verification-box">
        <div style="font-size: 4rem; color: #667eea; margin-bottom: 20px;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="mb-3">Verificación Requerida</h3>
        
        <?php if (isset($errorVerificacion)): ?>
            <div class="alert alert-danger"><?= $errorVerificacion ?></div>
        <?php endif; ?>
        
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle-fill me-2"></i>
            Hemos enviado un código de 6 dígitos a tu teléfono registrado
        </div>
        
        <p class="text-muted mb-3">Ingresa el código para cambiar tu contraseña</p>
        
        <form method="POST" id="formVerificacion">
            <input type="hidden" name="verificar_codigo" value="1">
            <input type="text" 
                   name="codigo_verificacion" 
                   id="codigoInput"
                   class="form-control code-input-verify" 
                   maxlength="6" 
                   pattern="\d{6}"
                   inputmode="numeric"
                   placeholder="000000"
                   autocomplete="off"
                   required
                   autofocus>
            
            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                <i class="bi bi-check-circle me-2"></i>
                Verificar Código
            </button>
        </form>
        
        <div class="mt-3">
            <small class="text-muted">
                ¿No recibiste el código? 
                <a href="#" id="otp-reenviar" class="text-decoration-none">Reenviar</a>
            </small>
        </div>
    </div>
</div>

<script>
// Auto-submit cuando se completan 6 dígitos
document.getElementById('codigoInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length === 6) {
        document.getElementById('formVerificacion').submit();
    }
});

// Animación de error si existe
<?php if (isset($errorVerificacion)): ?>
document.getElementById('codigoInput').classList.add('error-shake');
setTimeout(() => {
    document.getElementById('codigoInput').classList.remove('error-shake');
    document.getElementById('codigoInput').select();
}, 500);
<?php endif; ?>

// Reenviar código con enfriamiento (sin recargar)
(function(){
  const link = document.getElementById('otp-reenviar');
  if (!link) return;
  let locked = false; let timer = null; let secs = 0; const orig = link.textContent;
  link.addEventListener('click', async function(ev){
    ev.preventDefault(); if (locked) return;
    locked = true; secs = 60; link.classList.add('disabled');
    link.textContent = `Reenviar (${secs}s)`;
    timer = setInterval(()=>{
      secs--; link.textContent = `Reenviar (${secs}s)`;
      if (secs <= 0){ clearInterval(timer); link.classList.remove('disabled'); link.textContent = orig; locked = false; }
    }, 1000);
    try { await fetch(window.location.pathname + '?reenviar=1', { credentials:'same-origin' }); } catch(e){}
  });
})();
</script>
<?php endif; ?>

<div class="login-container" <?= $necesitaVerificacion ? 'style="filter: blur(5px); pointer-events: none;"' : '' ?>>
    <div class="text-center mb-4">
        <h2>🔒 Cambiar Contraseña</h2>
        <p class="text-muted">Por favor ingrese su contraseña actual y la nueva contraseña</p>
    </div>

    <?php if (isset($mensajeVerificacion)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($mensajeVerificacion) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($exito) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="formCambiarContraseña">
        <div class="form-group mb-4">
            <label for="actual" class="form-label">Contraseña Actual</label>
            <div class="password-field">
                <input type="password" class="form-control" id="actual" name="actual" required 
                       placeholder="Ingrese su contraseña actual">
                <button type="button" class="toggle-password" onclick="togglePassword('actual')">
                    <i class="far fa-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="nueva" class="form-label">Nueva Contraseña</label>
            <div class="password-field">
                <input type="password" class="form-control" id="nueva" name="nueva" required 
                       placeholder="Ingrese su nueva contraseña" 
                       onkeyup="checkPasswordStrength(this.value)">
                <button type="button" class="toggle-password" onclick="togglePassword('nueva')">
                    <i class="far fa-eye"></i>
                </button>
            </div>
            <div class="password-strength mt-2">
                <div id="strengthBar" class="strength-bar"></div>
            </div>
            <div class="password-requirements mt-3">
                <p>La contraseña debe contener:</p>
                <div class="requirement" id="length">
                    <i class="far fa-circle"></i>
                    <span>Al menos 8 caracteres</span>
                </div>
                <div class="requirement" id="uppercase">
                    <i class="far fa-circle"></i>
                    <span>Al menos una letra mayúscula</span>
                </div>
                <div class="requirement" id="number">
                    <i class="far fa-circle"></i>
                    <span>Al menos un número</span>
                </div>
                <div class="requirement" id="special">
                    <i class="far fa-circle"></i>
                    <span>Al menos un carácter especial</span>
                </div>
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="confirmar" class="form-label">Confirmar Nueva Contraseña</label>
            <div class="password-field">
                <input type="password" class="form-control" id="confirmar" name="confirmar" required 
                       placeholder="Confirme su nueva contraseña">
                <button type="button" class="toggle-password" onclick="togglePassword('confirmar')">
                    <i class="far fa-eye"></i>
                </button>
            </div>
            <small id="passwordMatch" class="text-muted d-block mt-1"></small>
        </div>

        <button type="submit" class="btn btn-brand" id="submitBtn" disabled>
            <i class="fas fa-save me-2"></i> Actualizar Contraseña
        </button>

        <div class="text-center mt-4">
            <a href="Admin.php" class="btn btn-outline-brand">
                <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
            </a>
        </div>
    </form>
</div>
</main>

<script>
// Flujo OTP duplicado eliminado; el modal del servidor maneja la verificación.
</script>
<script src="../../Public/js/cambiar_contraseña.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
