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
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <link rel="stylesheet" href="../../Public/css/login.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 5px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            background-color: #3498db;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-primary:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        .password-requirements {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 0.9em;
            color: #495057;
        }
        .password-requirements p {
            margin-top: 0;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .requirement {
            margin: 8px 0;
            display: flex;
            align-items: center;
            font-size: 0.95em;
        }
        .requirement.valid {
            color: #2ecc71;
        }
        .requirement.invalid {
            color: #e74c3c;
        }
        .requirement i {
            margin-right: 10px;
            font-size: 1.1em;
        }
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
            background: none;
            border: none;
            font-size: 1.1em;
            padding: 5px;
        }
        .toggle-password:hover {
            color: #3498db;
        }
        .password-strength {
            height: 6px;
            background-color: #ecf0f1;
            margin: 10px 0 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 5px;
            font-size: 0.95em;
        }
        .alert-danger {
            color: #e74c3c;
            background-color: #fadbd8;
            border-color: #f8d7da;
        }
        .alert-success {
            color: #27ae60;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .btn-outline-secondary {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 15px;
            color: #7f8c8d;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            color: #2c3e50;
            text-decoration: none;
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

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
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
