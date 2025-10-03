-- ===========================================
-- Reinicio limpio (opcional si vas a crear desde cero)
-- ===========================================
DROP DATABASE IF EXISTS aula_innovacion;
CREATE DATABASE aula_innovacion CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE aula_innovacion;

-- ===========================================
-- Tablas base (estructura final consolidada)
-- ===========================================

-- 1) Usuarios
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  correo VARCHAR(100) UNIQUE,
  contraseña VARCHAR(255),
  tipo_usuario ENUM('Administrador', 'Profesor', 'Encargado') NOT NULL,
  -- Verificación de correo
  verificado TINYINT(1) NOT NULL DEFAULT 0,
  verification_token VARCHAR(255) NULL,
  token_expira DATETIME NULL,
  -- Restablecimiento de contraseña
  reset_token VARCHAR(255) NULL,
  reset_expira DATETIME NULL,
  -- Baja lógica
  activo TINYINT(1) NOT NULL DEFAULT 1,
  -- Magic login (enlace de un solo uso)
  login_token VARCHAR(255) NULL,
  login_expira DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Aulas
CREATE TABLE aulas (
  id_aula INT AUTO_INCREMENT PRIMARY KEY,
  nombre_aula VARCHAR(100),
  capacidad INT,
  tipo ENUM('AIP','REGULAR') NOT NULL DEFAULT 'REGULAR',
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Equipos
CREATE TABLE equipos (
  id_equipo INT AUTO_INCREMENT PRIMARY KEY,
  nombre_equipo VARCHAR(100),
  tipo_equipo VARCHAR(50),
  estado ENUM('Disponible', 'Prestado') DEFAULT 'Disponible',
  stock INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Reservas
CREATE TABLE reservas (
  id_reserva INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  id_aula INT,
  fecha DATE,
  hora_inicio TIME,
  hora_fin TIME,
  CONSTRAINT fk_reservas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_reservas_aula FOREIGN KEY (id_aula) REFERENCES aulas(id_aula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Préstamos (histórico por equipo; preserva registros si se borra el equipo)
CREATE TABLE prestamos (
  id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  id_equipo INT NULL,
  id_aula INT NOT NULL,
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
  CONSTRAINT fk_prestamos_aulas FOREIGN KEY (id_aula) REFERENCES aulas(id_aula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Reservas canceladas (snapshot)
CREATE TABLE reservas_canceladas (
  id_cancelacion INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NULL,
  id_usuario INT NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  fecha_cancelacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  -- Snapshot de la reserva
  id_aula INT NULL,
  fecha DATE NULL,
  hora_inicio TIME NULL,
  hora_fin TIME NULL,
  CONSTRAINT fk_cancel_reserva FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL,
  CONSTRAINT fk_cancel_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_cancel_aula FOREIGN KEY (id_aula) REFERENCES aulas(id_aula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================
-- Modelo de packs y stock agregado por tipo
-- ===========================================

-- Stock agregado por tipo (no por unidad)
CREATE TABLE stock_equipos (
  tipo_equipo VARCHAR(50) PRIMARY KEY,
  stock_total INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Encabezado del préstamo por solicitud (un pack por fila)
CREATE TABLE prestamos_pack (
  id_pack INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_aula INT NOT NULL,
  fecha_prestamo DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NULL,
  estado ENUM('Prestado','Devuelto') NOT NULL DEFAULT 'Prestado',
  fecha_devolucion DATE NULL,
  comentario_devolucion TEXT NULL,
  CONSTRAINT fk_pack_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_pack_aula FOREIGN KEY (id_aula) REFERENCES aulas(id_aula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle del pack (tipos/cantidades, con complementos)
CREATE TABLE prestamos_pack_items (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  id_pack INT NOT NULL,
  tipo_equipo VARCHAR(50) NOT NULL,
  es_complemento TINYINT(1) NOT NULL DEFAULT 0,
  cantidad INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_items_pack FOREIGN KEY (id_pack) REFERENCES prestamos_pack(id_pack) ON DELETE CASCADE,
  INDEX idx_pack (id_pack),
  INDEX idx_tipo (tipo_equipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Opcional) Semilla de stock agregado desde equipos activos (si ya cargaste equipos por unidad)
-- Desde cero no inserta nada, pero es seguro ejecutar
INSERT INTO stock_equipos (tipo_equipo, stock_total)
SELECT e.tipo_equipo, COUNT(*) AS stock_total
FROM equipos e
WHERE e.activo = 1
GROUP BY e.tipo_equipo
ON DUPLICATE KEY UPDATE stock_total = VALUES(stock_total);

-- ===========================================
-- Configuración de seguridad (primer login admin)
-- ===========================================
-- La app usará esta tabla para “destruir” la ruta de alta de administradores
-- tras el primer login de un usuario Administrador (lógica en tu PHP).
CREATE TABLE app_config (
  cfg_key VARCHAR(100) PRIMARY KEY,
  cfg_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valor inicial: instalación no completada (la app lo pondrá en '1' en el primer login de un Administrador)
INSERT INTO app_config (cfg_key, cfg_value)
VALUES ('setup_completed', '0')
-- Índices útiles (opcional)
-- ===========================================
CREATE INDEX idx_prestamos_usuario_fecha ON prestamos (id_usuario, fecha_prestamo);
CREATE INDEX idx_prestamos_estado_fecha ON prestamos (estado, fecha_prestamo);

-- ===========================================
-- Notificaciones internas (in-app)
-- ===========================================
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

CREATE INDEX idx_reservas_usuario_fecha ON reservas (id_usuario, fecha, hora_inicio);