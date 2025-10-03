<?php
session_start();
require '../models/UsuarioModel.php';
require_once __DIR__ . '/../config/conexion.php';

$usuarioModel = new UsuarioModel();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';

    if ($correo === '' || $contraseña === '') {
        // Guardar mensaje en sesión y redirigir de vuelta al login
        $_SESSION['login_msg'] = "⚠️ Por favor, completa todos los campos.";
        $_SESSION['login_msg_type'] = 'error';
        header('Location: ../../Public/index.php');
        exit();
    } else {
        $user = $usuarioModel->obtenerPorCorreo($correo);

        if ($user && password_verify($contraseña, $user['contraseña'])) {
            if (isset($user['activo']) && (int)$user['activo'] !== 1) {
                $_SESSION['login_msg'] = "⛔ Tu cuenta está dada de baja. Contacta al administrador.";
                $_SESSION['login_msg_type'] = 'error';
                header('Location: ../../Public/index.php');
                exit();
            }
            // Requiere verificación de correo para TODOS los tipos de usuario
            if (isset($user['verificado']) && (int)$user['verificado'] !== 1) {
                $_SESSION['login_msg'] = "✉️ Debes verificar tu correo antes de iniciar sesión. Revisa tu bandeja o solicita reenviar verificación.";
                $_SESSION['login_msg_type'] = 'error';
                header('Location: ../../Public/index.php');
                exit();
            }
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['usuario'] = $user['nombre'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['tipo'] = $user['tipo_usuario'];
            // Si es el primer login de un Administrador, marcar setup_completed = '1'
            if (($user['tipo_usuario'] ?? '') === 'Administrador') {
                try {
                    $stmtCfg = $conexion->prepare("SELECT cfg_value FROM app_config WHERE cfg_key='setup_completed'");
                    $stmtCfg->execute();
                    $val = $stmtCfg->fetchColumn();
                    if ($val === false) {
                        // crear clave si no existe
                        $conexion->prepare("INSERT INTO app_config (cfg_key, cfg_value) VALUES ('setup_completed','1')")->execute();
                    } elseif ((string)$val !== '1') {
                        $conexion->prepare("UPDATE app_config SET cfg_value='1' WHERE cfg_key='setup_completed'")->execute();
                    }
                } catch (\Throwable $e) {
                    // no bloquear login por este paso; solo loguear
                    error_log('setup_completed update failed: ' . $e->getMessage());
                }
            }
            header('Location: ../view/Dashboard.php');
            exit();
        } else {
            // Mensaje de error y retorno al login
            $_SESSION['login_msg'] = "❌ Credenciales incorrectas. Verifica tu correo y contraseña.";
            $_SESSION['login_msg_type'] = 'error';
            header('Location: ../../Public/index.php');
            exit();
        }
    }
}
// Si llega por GET u otra vía, volver al login
header('Location: ../../Public/index.php');
exit();
