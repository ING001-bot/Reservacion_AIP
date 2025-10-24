DROP DATABASE IF EXISTS aula_innovacion;
CREATE DATABASE aula_innovacion CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE aula_innovacion;

-- Tabla: usuarios
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(100) NOT NULL UNIQUE,
  contraseña VARCHAR(255) NOT NULL,
  tipo_usuario ENUM('Administrador', 'Profesor', 'Encargado') NOT NULL DEFAULT 'Profesor',
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  verification_token VARCHAR(255) NULL,
  token_expira DATETIME NULL,
  reset_token VARCHAR(255) NULL,
  reset_expira DATETIME NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  login_token VARCHAR(255) NULL,
  login_expira DATETIME NULL,
  telefono VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: aulas
CREATE TABLE aulas (
  id_aula INT AUTO_INCREMENT PRIMARY KEY,
  nombre_aula VARCHAR(100),
  capacidad INT,
  tipo ENUM('AIP','REGULAR') NOT NULL DEFAULT 'REGULAR',
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: equipos
CREATE TABLE equipos (
  id_equipo INT AUTO_INCREMENT PRIMARY KEY,
  nombre_equipo VARCHAR(100) NOT NULL,
  tipo_equipo VARCHAR(50) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservas (
  id_reserva INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  id_aula INT NULL,
  fecha DATE,
  hora_inicio TIME,
  hora_fin TIME,
  CONSTRAINT fk_reservas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_reservas_aula FOREIGN KEY (id_aula) REFERENCES aulas(id_aula) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE prestamos (
  id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  id_equipo INT NULL,
  id_aula INT NULL,
  fecha_prestamo DATE,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NULL,
  fecha_devolucion DATE NULL,
  comentario_devolucion TEXT NULL,
  estado ENUM('Prestado', 'Devuelto') DEFAULT 'Prestado',
  CONSTRAINT fk_prestamos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_prestamos_equipo FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT fk_prestamos_aulas FOREIGN KEY (id_aula) REFERENCES aulas(id_aula) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservas_canceladas (
  id_cancelacion INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NULL,
  id_usuario INT NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  fecha_cancelacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  id_aula INT NULL,
  fecha DATE NULL,
  hora_inicio TIME NULL,
  hora_fin TIME NULL,
  CONSTRAINT fk_cancel_reserva FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL,
  CONSTRAINT fk_cancel_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_cancel_aula FOREIGN KEY (id_aula) REFERENCES aulas(id_aula) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabla: app_config
CREATE TABLE app_config (
  cfg_key VARCHAR(100) PRIMARY KEY,
  cfg_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO app_config (cfg_key, cfg_value)
VALUES ('setup_completed', '0');

-- Índices
CREATE INDEX idx_prestamos_usuario_fecha ON prestamos (id_usuario, fecha_prestamo);
CREATE INDEX idx_prestamos_estado_fecha ON prestamos (estado, fecha_prestamo);
CREATE INDEX idx_reservas_usuario_fecha ON reservas (id_usuario, fecha, hora_inicio);

-- Tabla: notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
  id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  mensaje TEXT NOT NULL,
  url VARCHAR(255) NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: verification_codes (SMS)
CREATE TABLE IF NOT EXISTS verification_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code VARCHAR(6) NOT NULL,
  action_type ENUM('reserva','prestamo','cambio_clave') NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_vc_user ON verification_codes (user_id);
CREATE INDEX idx_vc_action ON verification_codes (action_type);
CREATE INDEX idx_vc_expires ON verification_codes (expires_at);
CREATE INDEX idx_vc_used ON verification_codes (used);

CREATE TABLE tipos_equipo (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;