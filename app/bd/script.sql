-- ============================================
-- SISTEMA DE RESERVACIÓN AIP
-- Base de Datos Optimizada - Solo tablas en uso
-- Versión: 2.0 (Limpia y actualizada)
-- ============================================

DROP DATABASE IF EXISTS aula_innovacion;
CREATE DATABASE aula_innovacion CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE aula_innovacion;

-- ============================================
-- TABLA: usuarios
-- Gestión de usuarios del sistema
-- Nota: El teléfono NO se verifica, solo se usa para enviar códigos OTP
-- ============================================
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(100) NOT NULL UNIQUE,
  contraseña VARCHAR(255) NOT NULL,
  tipo_usuario ENUM('Administrador', 'Profesor', 'Encargado') NOT NULL DEFAULT 'Profesor',
  telefono VARCHAR(20) NULL,
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  verification_token VARCHAR(255) NULL,
  token_expira DATETIME NULL,
  reset_token VARCHAR(255) NULL,
  reset_expira DATETIME NULL,
  login_token VARCHAR(255) NULL,
  login_expira DATETIME NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_correo (correo),
  INDEX idx_tipo (tipo_usuario),
  INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Usuarios del sistema (verificación solo por email)';

-- ============================================
-- TABLA: aulas
-- Gestión de aulas disponibles
-- ============================================
CREATE TABLE aulas (
  id_aula INT AUTO_INCREMENT PRIMARY KEY,
  nombre_aula VARCHAR(100) NOT NULL,
  capacidad INT NOT NULL DEFAULT 0,
  tipo ENUM('AIP','REGULAR') NOT NULL DEFAULT 'REGULAR',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_tipo_activo (tipo, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Aulas disponibles para reserva';

-- ============================================
-- TABLA: tipos_equipo
-- Tipos de equipos disponibles
-- ============================================
CREATE TABLE tipos_equipo (
  id_tipo INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Categorías de equipos';

-- ============================================
-- TABLA: equipos
-- Inventario de equipos
-- ============================================
CREATE TABLE equipos (
  id_equipo INT AUTO_INCREMENT PRIMARY KEY,
  nombre_equipo VARCHAR(100) NOT NULL,
  tipo_equipo VARCHAR(50) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  stock_maximo INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_tipo (tipo_equipo),
  INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Inventario de equipos disponibles';

-- ============================================
-- TABLA: reservas
-- Reservas de aulas
-- ============================================
CREATE TABLE reservas (
  id_reserva INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_aula INT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  CONSTRAINT fk_reservas_usuario FOREIGN KEY (id_usuario) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_reservas_aula FOREIGN KEY (id_aula) 
    REFERENCES aulas(id_aula) ON DELETE SET NULL,
  INDEX idx_usuario_fecha (id_usuario, fecha),
  INDEX idx_fecha_hora (fecha, hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Reservas de aulas';

-- ============================================
-- TABLA: prestamos
-- Préstamos de equipos
-- ============================================
CREATE TABLE prestamos (
  id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_equipo INT NULL,
  id_aula INT NULL,
  fecha_prestamo DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NULL,
  fecha_devolucion DATE NULL,
  comentario_devolucion TEXT NULL,
  estado ENUM('Prestado', 'Devuelto') NOT NULL DEFAULT 'Prestado',
  CONSTRAINT fk_prestamos_usuario FOREIGN KEY (id_usuario) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_prestamos_equipo FOREIGN KEY (id_equipo) 
    REFERENCES equipos(id_equipo) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_prestamos_aula FOREIGN KEY (id_aula) 
    REFERENCES aulas(id_aula) ON DELETE SET NULL,
  INDEX idx_usuario_fecha (id_usuario, fecha_prestamo),
  INDEX idx_estado_fecha (estado, fecha_prestamo),
  INDEX idx_equipo (id_equipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Préstamos de equipos';

-- ============================================
-- TABLA: reservas_canceladas
-- Historial de reservas canceladas
-- ============================================
CREATE TABLE reservas_canceladas (
  id_cancelacion INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NULL,
  id_usuario INT NOT NULL,
  id_aula INT NULL,
  fecha DATE NULL,
  hora_inicio TIME NULL,
  hora_fin TIME NULL,
  motivo VARCHAR(255) NOT NULL,
  fecha_cancelacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cancel_reserva FOREIGN KEY (id_reserva) 
    REFERENCES reservas(id_reserva) ON DELETE SET NULL,
  CONSTRAINT fk_cancel_usuario FOREIGN KEY (id_usuario) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_cancel_aula FOREIGN KEY (id_aula) 
    REFERENCES aulas(id_aula) ON DELETE SET NULL,
  INDEX idx_fecha_cancel (fecha_cancelacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de cancelaciones';

-- ============================================
-- TABLA: notificaciones
-- Sistema de notificaciones
-- ============================================
CREATE TABLE notificaciones (
  id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  mensaje TEXT NOT NULL,
  url VARCHAR(255) NULL,
  metadata JSON NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_usuario FOREIGN KEY (id_usuario) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_usuario_leida (id_usuario, leida),
  INDEX idx_creada (creada_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Notificaciones del sistema';

-- ============================================
-- TABLA: verification_codes
-- Códigos OTP para acciones críticas (NO para verificar teléfono)
-- Se envían al profesor cuando intenta: hacer reserva, préstamo o cambiar contraseña
-- ============================================
CREATE TABLE verification_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code VARCHAR(6) NOT NULL,
  action_type ENUM('reserva','prestamo','cambio_clave') NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vc_user FOREIGN KEY (user_id) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_action (action_type),
  INDEX idx_expires (expires_at),
  INDEX idx_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Códigos OTP para acciones críticas';

-- ============================================
-- TABLA: configuracion_usuario
-- Configuración de perfil de usuario
-- ============================================
CREATE TABLE configuracion_usuario (
  id_configuracion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL UNIQUE,
  foto_perfil VARCHAR(255) NULL,
  bio TEXT NULL,
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_config_usuario FOREIGN KEY (id_usuario) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configuración de perfiles de usuario';

-- ============================================
-- TABLA: mantenimiento_sistema
-- Registro de mantenimientos ejecutados
-- ============================================
CREATE TABLE mantenimiento_sistema (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ultima_ejecucion DATETIME NOT NULL,
  ejecutado_por INT NOT NULL,
  CONSTRAINT fk_mant_usuario FOREIGN KEY (ejecutado_por) 
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_fecha (ultima_ejecucion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de mantenimientos del sistema';

-- ============================================
-- TABLA: app_config
-- Configuración general del sistema
-- ============================================
CREATE TABLE app_config (
  cfg_key VARCHAR(100) PRIMARY KEY,
  cfg_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configuración del sistema';

-- ============================================
-- DATOS INICIALES
-- ============================================
INSERT INTO app_config (cfg_key, cfg_value) VALUES ('setup_completed', '0');

-- ============================================
-- FIN DEL SCRIPT
-- Base de datos lista para usar
-- ============================================
