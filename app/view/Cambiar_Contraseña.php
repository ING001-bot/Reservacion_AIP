<?php
// Incluir el controlador
require_once __DIR__ . '/../controllers/CambiarContraseñaController.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Aulas de Innovación</title>
    <link rel="stylesheet" href="/Reservacion_AIP/Public/css/brand.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .password-field {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
        }
        
        .password-strength {
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .password-requirements {
            font-size: 0.875rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
        
        .requirement i {
            margin-right: 0.5rem;
            width: 16px;
        }
        
        .requirement.valid {
            color: #28a745;
        }
        
        .requirement.invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="text-center mb-4">
        <h2>🔒 Cambiar Contraseña</h2>
        <p class="text-muted">Por favor ingrese su contraseña actual y la nueva contraseña</p>
    </div>

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

        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
            <i class="fas fa-save me-2"></i> Actualizar Contraseña
        </button>

        <div class="text-center mt-4">
            <a href="Admin.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
            </a>
        </div>
    </form>
</div>
</main>

<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.parentElement.querySelector('.toggle-password i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strengthBar');
        const length = document.getElementById('length');
        const uppercase = document.getElementById('uppercase');
        const number = document.getElementById('number');
        const special = document.getElementById('special');
        const submitBtn = document.getElementById('submitBtn');
        
        // Reset all requirements
        [length, uppercase, number, special].forEach(el => {
            if (el) {
                el.classList.remove('valid', 'invalid');
                const icon = el.querySelector('i');
                if (icon) icon.className = 'far fa-circle';
            }
        });
        
        let strength = 0;
        
        // Check length
        if (password.length >= 8) {
            strength++;
            updateRequirement(length, true);
        } else {
            updateRequirement(length, false);
        }
        
        // Check uppercase
        if (/[A-Z]/.test(password)) {
            strength++;
            updateRequirement(uppercase, true);
        } else {
            updateRequirement(uppercase, false);
        }
        
        // Check number
        if (/[0-9]/.test(password)) {
            strength++;
            updateRequirement(number, true);
        } else {
            updateRequirement(number, false);
        }
        
        // Check special character
        if (/[^A-Za-z0-9]/.test(password)) {
            strength++;
            updateRequirement(special, true);
        } else {
            updateRequirement(special, false);
        }
        
        // Update strength bar
        if (strengthBar) {
            const strengthPercent = (strength / 4) * 100;
            strengthBar.style.width = strengthPercent + '%';
            
            // Update strength bar color
            if (strength <= 1) {
                strengthBar.style.backgroundColor = '#e74c3c'; // Red
            } else if (strength === 2) {
                strengthBar.style.backgroundColor = '#f39c12'; // Orange
            } else if (strength === 3) {
                strengthBar.style.backgroundColor = '#3498db'; // Blue
            } else {
                strengthBar.style.backgroundColor = '#2ecc71'; // Green
            }
        }
        
        // Check if all requirements are met
        const allRequirementsMet = document.querySelectorAll('.requirement.valid').length === 4;
        if (submitBtn) {
            submitBtn.disabled = !allRequirementsMet;
        }
        
        // Check if passwords match
        const confirmField = document.getElementById('confirmar');
        const matchText = document.getElementById('passwordMatch');
        
        if (confirmField && matchText) {
            const confirmPassword = confirmField.value;
            
            if (confirmPassword) {
                if (password === confirmPassword) {
                    matchText.textContent = 'Las contraseñas coinciden';
                    matchText.style.color = '#2ecc71';
                    if (submitBtn) submitBtn.disabled = !allRequirementsMet;
                } else {
                    matchText.textContent = 'Las contraseñas no coinciden';
                    matchText.style.color = '#e74c3c';
                    if (submitBtn) submitBtn.disabled = true;
                }
            } else {
                matchText.textContent = '';
                if (submitBtn) submitBtn.disabled = true;
            }
        }
    }
    
    function updateRequirement(element, isValid) {
        if (!element) return;
        
        const icon = element.querySelector('i');
        if (!icon) return;
        
        if (isValid) {
            element.classList.add('valid');
            element.classList.remove('invalid');
            icon.className = 'fas fa-check-circle';
        } else {
            element.classList.add('invalid');
            element.classList.remove('valid');
            icon.className = 'far fa-times-circle';
        }
    }
    
    // Add event listeners when the document is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Confirm password field
        const confirmField = document.getElementById('confirmar');
        if (confirmField) {
            confirmField.addEventListener('input', function() {
                const password = document.getElementById('nueva').value;
                const confirmPassword = this.value;
                const matchText = document.getElementById('passwordMatch');
                const submitBtn = document.getElementById('submitBtn');
                
                if (password && confirmPassword) {
                    if (password === confirmPassword) {
                        matchText.textContent = 'Las contraseñas coinciden';
                        matchText.style.color = '#2ecc71';
                        if (submitBtn) submitBtn.disabled = false;
                    } else {
                        matchText.textContent = 'Las contraseñas no coinciden';
                        matchText.style.color = '#e74c3c';
                        if (submitBtn) submitBtn.disabled = true;
                    }
                } else {
                    matchText.textContent = '';
                    if (submitBtn) submitBtn.disabled = true;
                }
            });
        }
        
        // Form validation
        const form = document.getElementById('formCambiarContraseña');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('nueva').value;
                const confirmPassword = document.getElementById('confirmar').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                    return false;
                }
                
                return true;
            });
        }
    });
</script>
</body>
</html>
