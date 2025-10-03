CREATE DATABASE IF NOT EXISTS aula_innovacion;
USE aula_innovacion;
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    correo VARCHAR(100) UNIQUE,
    contraseña VARCHAR(255),
    tipo_usuario ENUM('Administrador', 'Profesor', 'Encargado') NOT NULL
);
CREATE TABLE aulas (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    nombre_aula VARCHAR(100),
    capacidad INT
);
CREATE TABLE equipos (
    id_equipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_equipo VARCHAR(100),
    tipo_equipo VARCHAR(50),
    estado ENUM('Disponible', 'Prestado') DEFAULT 'Disponible'
);
CREATE TABLE reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_aula INT,
    fecha DATE,
    hora_inicio TIME,
    hora_fin TIME,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_aula) REFERENCES aulas(id_aula)
);
CREATE TABLE prestamos (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_equipo INT,
    fecha_prestamo DATE,
    fecha_devolucion DATE NULL,
    estado ENUM('Prestado', 'Devuelto') DEFAULT 'Prestado',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo)
);
CREATE TABLE reservas_canceladas (
    id_cancelacion INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NULL,
    id_usuario INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_cancelacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Snapshot de la reserva en el momento de cancelación
    id_aula INT NULL,
    fecha DATE NULL,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_aula) REFERENCES aulas(id_aula)
);
ALTER TABLE prestamos
ADD COLUMN hora_inicio TIME NOT NULL
AFTER id_equipo,
    ADD COLUMN hora_fin TIME NULL
AFTER hora_inicio;
ALTER TABLE aulas
ADD COLUMN tipo ENUM('AIP', 'REGULAR') NOT NULL DEFAULT 'REGULAR'
AFTER capacidad;
ALTER TABLE prestamos
ADD COLUMN id_aula INT NOT NULL
AFTER id_prestamo;
ALTER TABLE prestamos
ADD CONSTRAINT fk_prestamos_aulas FOREIGN KEY (id_aula) REFERENCES aulas(id_aula);
ALTER TABLE equipos
ADD COLUMN stock INT NOT NULL DEFAULT 0;
ALTER TABLE equipos
ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
-- Verificación de correo electrónico
ALTER TABLE usuarios
ADD COLUMN verificado TINYINT(1) NOT NULL DEFAULT 0
AFTER tipo_usuario;
ALTER TABLE usuarios
ADD COLUMN verification_token VARCHAR(255) NULL
AFTER verificado;
ALTER TABLE usuarios
ADD COLUMN token_expira DATETIME NULL
AFTER verification_token;
-- Restablecimiento de contraseña
ALTER TABLE usuarios
ADD COLUMN reset_token VARCHAR(255) NULL
AFTER token_expira;
ALTER TABLE usuarios
ADD COLUMN reset_expira DATETIME NULL
AFTER reset_token;

-- Baja lógica
ALTER TABLE usuarios
ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER reset_expira;

ALTER TABLE aulas
ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER tipo;

-- Magic login (enlace de un solo uso)
ALTER TABLE usuarios
ADD COLUMN login_token VARCHAR(255) NULL AFTER activo;
ALTER TABLE usuarios
ADD COLUMN login_expira DATETIME NULL AFTER login_token;

-- Comentario de devolución de equipos
ALTER TABLE prestamos
ADD COLUMN comentario_devolucion TEXT NULL AFTER fecha_devolucion;

-- Packs de préstamos (encabezado + detalle) y stock por tipo
-- Encabezado de préstamo (un registro por solicitud)
CREATE TABLE IF NOT EXISTS prestamos_pack (
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
);

-- Detalle de ítems prestados dentro del pack (por tipo/cantidad)
CREATE TABLE IF NOT EXISTS prestamos_pack_items (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_pack INT NOT NULL,
    tipo_equipo VARCHAR(50) NOT NULL,
    es_complemento TINYINT(1) NOT NULL DEFAULT 0,
    cantidad INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_items_pack FOREIGN KEY (id_pack) REFERENCES prestamos_pack(id_pack) ON DELETE CASCADE
);

-- Stock por tipo de equipo (agregada)
CREATE TABLE IF NOT EXISTS stock_equipos (
    tipo_equipo VARCHAR(50) PRIMARY KEY,
    stock_total INT NOT NULL
);

-- Semilla/actualización inicial de stock a partir de 'equipos' activos
INSERT INTO stock_equipos (tipo_equipo, stock_total)
SELECT e.tipo_equipo, COUNT(*) as stock_total
FROM equipos e
WHERE e.activo = 1
GROUP BY e.tipo_equipo
ON DUPLICATE KEY UPDATE stock_total = VALUES(stock_total);