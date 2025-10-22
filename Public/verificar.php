<?php
require_once __DIR__ . '/../app/init.php';

// Verificar si hay una sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$error = $_GET['error'] ?? '';

// Validar acción
if (!in_array($action, ['reserva', 'prestamo', 'cambio_clave'])) {
    header('Location: index.php');
    exit;
}

// Obtener información del usuario
require_once __DIR__ . '/../app/models/UsuarioModel.php';
$usuarioModel = new UsuarioModel($conexion);
$usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación por SMS - Sistema de Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-container {
            max-width: 450px;
            width: 90%;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .verification-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .code-input {
            letter-spacing: 12px;
            font-size: 2rem;
            text-align: center;
            height: 70px;
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: bold;
        }
        .code-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: scale(1.02);
        }
        .error-shake {
            animation: shake 0.5s, errorPulse 0.5s;
            border-color: #dc3545 !important;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        @keyframes errorPulse {
            0%, 100% { background-color: white; }
            50% { background-color: #ffe0e0; }
        }
        .success-animation {
            animation: successPulse 0.6s;
        }
        @keyframes successPulse {
            0%, 100% { background-color: white; }
            50% { background-color: #d4edda; }
        }
        .btn-verify {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .tooltip-error {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
            animation: tooltipBounce 0.5s;
            z-index: 1000;
        }
        .tooltip-error::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid #dc3545;
        }
        @keyframes tooltipBounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-5px); }
        }
        .phone-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }
        .sent-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 10px;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container text-center">
            <div class="verification-icon">
                <i class="bi bi-phone-vibrate"></i>
            </div>
            <h3 class="mb-4">Verificación por SMS</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="sent-badge mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>
                Código enviado exitosamente
            </div>
            
            <p class="mb-4">Hemos enviado un código de verificación de <strong>6 dígitos</strong> al número terminado en <span class="phone-number">****<?php echo htmlspecialchars(substr($usuario['telefono'], -4)); ?></span>. Por favor ingrésalo a continuación:</p>
            
            <form id="verificationForm" action="/Sistema_reserva_AIP/app/controllers/VerificationController.php" method="POST">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="action_type" value="<?php echo htmlspecialchars($action); ?>">
                
                <div class="mb-3">
                    <input type="text" 
                           id="verificationCode" 
                           name="verification_code" 
                           class="form-control form-control-lg text-center code-input" 
                           maxlength="6" 
                           pattern="\d{6}" 
                           inputmode="numeric" 
                           autocomplete="one-time-code"
                           autofocus
                           required>
                    <div class="invalid-feedback">
                        Por favor ingresa el código de 6 dígitos
                    </div>
                </div>
                
                <button type="submit" class="btn btn-verify btn-primary btn-lg w-100 mb-3">
                    <i class="bi bi-shield-check me-2"></i>
                    Verificar Código
                </button>
                
                <div class="text-muted small mt-3">
                    ¿No recibiste el código? 
                    <a href="#" id="resendCode" class="text-decoration-none">Reenviar código</a>
                    <span id="countdown" class="d-none">(60)</span>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('verificationForm');
            const codeInput = document.getElementById('verificationCode');
            const resendLink = document.getElementById('resendCode');
            const countdownSpan = document.getElementById('countdown');
            let countdown = 60;
            let countdownInterval;

            // Validar formato del código
            codeInput.addEventListener('input', function(e) {
                // Solo permitir números
                this.value = this.value.replace(/\D/g, '');
                
                // Auto-enfocar el siguiente campo (si se implementan inputs separados)
                if (this.value.length === 6) {
                    form.submit();
                }
            });

            // Función para mostrar tooltip de error
            function showErrorTooltip(message) {
                // Remover tooltip anterior si existe
                const existingTooltip = document.querySelector('.tooltip-error');
                if (existingTooltip) {
                    existingTooltip.remove();
                }
                
                // Crear nuevo tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-error';
                tooltip.textContent = message;
                
                // Agregar tooltip al contenedor del input
                const inputContainer = codeInput.parentElement;
                inputContainer.style.position = 'relative';
                inputContainer.appendChild(tooltip);
                
                // Remover tooltip después de 3 segundos
                setTimeout(() => {
                    tooltip.style.animation = 'fadeOut 0.3s';
                    setTimeout(() => tooltip.remove(), 300);
                }, 3000);
            }

            // Manejar envío del formulario
            form.addEventListener('submit', function(e) {
                if (!codeInput.value.match(/^\d{6}$/)) {
                    e.preventDefault();
                    codeInput.classList.add('is-invalid', 'error-shake');
                    showErrorTooltip('⚠️ Ingresa un código de 6 dígitos');
                    setTimeout(() => codeInput.classList.remove('error-shake'), 500);
                    return false;
                }
                return true;
            });
            
            // Detectar error de verificación desde el servidor
            <?php if ($error && strpos($error, 'inválido') !== false): ?>
            window.addEventListener('DOMContentLoaded', function() {
                codeInput.classList.add('error-shake');
                showErrorTooltip('❌ Código incorrecto. Intenta nuevamente');
                setTimeout(() => codeInput.classList.remove('error-shake'), 500);
                codeInput.select();
            });
            <?php endif; ?>

            // Manejar reenvío de código
            function startCountdown() {
                resendLink.classList.add('d-none');
                countdownSpan.classList.remove('d-none');
                countdown = 60;
                updateCountdown();
                
                countdownInterval = setInterval(() => {
                    countdown--;
                    updateCountdown();
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        resendLink.classList.remove('d-none');
                        countdownSpan.classList.add('d-none');
                    }
                }, 1000);
            }
            
            function updateCountdown() {
                countdownSpan.textContent = `(${countdown})`;
            }
            
            resendLink.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Deshabilitar el enlace temporalmente
                this.style.pointerEvents = 'none';
                
                fetch('/Sistema_reserva_AIP/app/controllers/VerificationController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=resend&action_type=<?php echo htmlspecialchars($action); ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        startCountdown();
                    } else {
                        alert('Error al reenviar el código: ' + (data.error || 'Error desconocido'));
                        this.style.pointerEvents = 'auto';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.style.pointerEvents = 'auto';
                });
            });
            
            // Iniciar cuenta regresiva al cargar la página
            startCountdown();
        });
    </script>
</body>
</html>
