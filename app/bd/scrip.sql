CREATE DATABASE IF NOT EXISTS aula_innovacion;
USE aula_innovacion;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    correo VARCHAR(100) UNIQUE,
    contraseña VARCHAR(255),
    tipo_usuario ENUM('Administrador','Profesor','Encargado') NOT NULL
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
    estado ENUM('Disponible','Prestado') DEFAULT 'Disponible'
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
    estado ENUM('Prestado','Devuelto') DEFAULT 'Prestado',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo)
);
