-- Backup completo de la base de datos
-- Fecha: 2025-11-25 16:15:15
-- Generado por Sistema AIP

SET FOREIGN_KEY_CHECKS=0;

-- Tabla: app_config
DROP TABLE IF EXISTS `app_config`;
CREATE TABLE `app_config` (
  `cfg_key` varchar(100) NOT NULL,
  `cfg_value` varchar(255) NOT NULL,
  PRIMARY KEY (`cfg_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de app_config
INSERT INTO `app_config` (`cfg_key`, `cfg_value`) VALUES ('setup_completed', '1');

-- Tabla: aulas
DROP TABLE IF EXISTS `aulas`;
CREATE TABLE `aulas` (
  `id_aula` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_aula` varchar(100) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `tipo` enum('AIP','REGULAR') NOT NULL DEFAULT 'REGULAR',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_aula`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de aulas
INSERT INTO `aulas` (`id_aula`, `nombre_aula`, `capacidad`, `tipo`, `activo`) VALUES ('1', 'AIP1', '30', 'AIP', '1');
INSERT INTO `aulas` (`id_aula`, `nombre_aula`, `capacidad`, `tipo`, `activo`) VALUES ('2', 'AIP2', '30', 'AIP', '1');
INSERT INTO `aulas` (`id_aula`, `nombre_aula`, `capacidad`, `tipo`, `activo`) VALUES ('3', '1° A', '30', 'REGULAR', '1');
INSERT INTO `aulas` (`id_aula`, `nombre_aula`, `capacidad`, `tipo`, `activo`) VALUES ('4', '1° B', '30', 'REGULAR', '1');

-- Tabla: configuracion_usuario
DROP TABLE IF EXISTS `configuracion_usuario`;
CREATE TABLE `configuracion_usuario` (
  `id_configuracion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_configuracion`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `configuracion_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: equipos
DROP TABLE IF EXISTS `equipos`;
CREATE TABLE `equipos` (
  `id_equipo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_equipo` varchar(100) NOT NULL,
  `tipo_equipo` varchar(50) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_equipo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de equipos
INSERT INTO `equipos` (`id_equipo`, `nombre_equipo`, `tipo_equipo`, `stock`, `activo`) VALUES ('1', 'Laptop Acer', 'LAPTOP', '16', '1');
INSERT INTO `equipos` (`id_equipo`, `nombre_equipo`, `tipo_equipo`, `stock`, `activo`) VALUES ('2', 'Parlante', 'PARLANTE', '2', '1');
INSERT INTO `equipos` (`id_equipo`, `nombre_equipo`, `tipo_equipo`, `stock`, `activo`) VALUES ('3', 'Proyector Epson', 'PROYECTOR', '3', '1');
INSERT INTO `equipos` (`id_equipo`, `nombre_equipo`, `tipo_equipo`, `stock`, `activo`) VALUES ('4', 'Extension', 'EXTENSION', '3', '1');

-- Tabla: notificaciones
DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `creada_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de notificaciones
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('1', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-21, 06:45:00 - 07:55:00', 'Admin.php?view=historial_global', '0', '2025-11-20 11:38:37');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('2', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-21, 06:45:00 - 07:55:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 11:38:37');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('3', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-21, 15:15:00 - 17:50:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:03:24');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('4', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-21, 15:15:00 - 17:50:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:03:24');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('5', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-21, 13:00:00 - 13:45:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:32:41');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('6', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-21, 13:00:00 - 13:45:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:32:41');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('7', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-21, 17:50:00 - 18:35:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:36:37');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('8', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-21, 17:50:00 - 18:35:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:36:37');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('9', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-21, 07:55:00 - 09:25:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:37:22');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('10', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-21, 07:55:00 - 09:25:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:37:22');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('11', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-21, 09:25:00 - 10:10:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:38:44');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('12', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-21, 09:25:00 - 10:10:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:38:44');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('13', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-22, 07:10:00 - 08:40:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:59:20');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('14', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-22, 07:10:00 - 08:40:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:59:20');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('15', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-22, 08:40:00 - 10:10:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:59:34');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('16', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-22, 08:40:00 - 10:10:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:59:34');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('17', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-22, 10:30:00 - 11:15:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:59:45');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('18', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-22, 10:30:00 - 11:15:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:59:45');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('19', '3', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-22, 11:15:00 - 12:45:00', 'Admin.php?view=historial_global', '0', '2025-11-20 12:59:52');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('20', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-22, 11:15:00 - 12:45:00', 'Public/index.php?view=mis_reservas', '1', '2025-11-20 12:59:52');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('21', '2', 'Préstamo registrado', 'Tu préstamo fue registrado. Equipos: Laptop Acer, Proyector Epson, Extension. Fecha: 2025-11-24, 07:10-4.', '/Reservacion_AIP/Public/index.php?view=mis_prestamos', '1', '2025-11-23 18:37:30');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('22', '1', 'Nuevo préstamo de equipos', 'Nuevo préstamo registrado por Alberto Braga Cabrera. Equipos: Laptop Acer, Proyector Epson, Extension. Fecha: 2025-11-24, 07:10-4.', '/Reservacion_AIP/Admin.php?view=historial_global', '1', '2025-11-23 18:37:30');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('23', '2', 'Devolución registrada', 'Tu préstamo #1 fue marcado como devuelto.', 'Historial.php', '1', '2025-11-25 09:05:43');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('24', '2', 'Devolución registrada', 'Tu préstamo #2 fue marcado como devuelto.', 'Historial.php', '1', '2025-11-25 09:05:43');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('25', '2', 'Devolución registrada', 'Tu préstamo #3 fue marcado como devuelto.', 'Historial.php', '1', '2025-11-25 09:05:43');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('26', '2', 'Devolución registrada', 'Tu préstamo #1 fue marcado como devuelto.', 'Historial.php', '1', '2025-11-25 09:05:55');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('27', '2', 'Devolución registrada', 'Tu préstamo #2 fue marcado como devuelto.', 'Historial.php', '1', '2025-11-25 09:05:55');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('28', '2', 'Devolución registrada', 'Tu préstamo #3 fue marcado como devuelto.', 'Historial.php', '1', '2025-11-25 09:05:55');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('29', '4', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-26, 06:00:00 - 07:10:00', '/Reservacion_AIP/Admin.php?view=historial_global', '0', '2025-11-25 09:07:57');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('30', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-26, 06:00:00 - 07:10:00', '/Reservacion_AIP/Public/index.php?view=mis_reservas', '1', '2025-11-25 09:07:57');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('31', '4', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-26, 07:10:00 - 09:25:00', '/Reservacion_AIP/Admin.php?view=historial_global', '0', '2025-11-25 09:08:17');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('32', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-26, 07:10:00 - 09:25:00', '/Reservacion_AIP/Public/index.php?view=mis_reservas', '1', '2025-11-25 09:08:17');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('33', '4', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-26, 09:25:00 - 10:10:00', '/Reservacion_AIP/Admin.php?view=historial_global', '0', '2025-11-25 09:08:38');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('34', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-26, 09:25:00 - 10:10:00', '/Reservacion_AIP/Public/index.php?view=mis_reservas', '1', '2025-11-25 09:08:38');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('35', '4', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-26, 10:30:00 - 12:00:00', '/Reservacion_AIP/Admin.php?view=historial_global', '0', '2025-11-25 09:08:47');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('36', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-26, 10:30:00 - 12:00:00', '/Reservacion_AIP/Public/index.php?view=mis_reservas', '1', '2025-11-25 09:08:47');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('37', '4', 'Nueva reserva de aula', 'Nueva reserva de aula por Alberto Braga Cabrera. Aula: AIP1. Fecha: 2025-11-26, 12:00:00 - 12:45:00', '/Reservacion_AIP/Admin.php?view=historial_global', '0', '2025-11-25 09:26:04');
INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `url`, `leida`, `creada_en`) VALUES ('38', '2', 'Reserva confirmada', 'Tu reserva fue registrada. Aula: AIP1. Fecha: 2025-11-26, 12:00:00 - 12:45:00', '/Reservacion_AIP/Public/index.php?view=mis_reservas', '0', '2025-11-25 09:26:04');

-- Tabla: otp_tokens
DROP TABLE IF EXISTS `otp_tokens`;
CREATE TABLE `otp_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `purpose` varchar(32) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `sent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_purpose` (`id_usuario`,`purpose`),
  CONSTRAINT `otp_tokens_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: prestamos
DROP TABLE IF EXISTS `prestamos`;
CREATE TABLE `prestamos` (
  `id_prestamo` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `id_equipo` int(11) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `fecha_prestamo` date DEFAULT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `comentario_devolucion` text DEFAULT NULL,
  `estado` enum('Prestado','Devuelto') DEFAULT 'Prestado',
  PRIMARY KEY (`id_prestamo`),
  KEY `fk_prestamos_equipo` (`id_equipo`),
  KEY `fk_prestamos_aulas` (`id_aula`),
  KEY `idx_prestamos_usuario_fecha` (`id_usuario`,`fecha_prestamo`),
  KEY `idx_prestamos_estado_fecha` (`estado`,`fecha_prestamo`),
  CONSTRAINT `fk_prestamos_aulas` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON DELETE SET NULL,
  CONSTRAINT `fk_prestamos_equipo` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prestamos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de prestamos
INSERT INTO `prestamos` (`id_prestamo`, `id_usuario`, `id_equipo`, `id_aula`, `fecha_prestamo`, `hora_inicio`, `hora_fin`, `fecha_devolucion`, `comentario_devolucion`, `estado`) VALUES ('1', '2', '1', '4', '2025-11-24', '07:10:00', '09:30:00', '2025-11-25', NULL, 'Devuelto');
INSERT INTO `prestamos` (`id_prestamo`, `id_usuario`, `id_equipo`, `id_aula`, `fecha_prestamo`, `hora_inicio`, `hora_fin`, `fecha_devolucion`, `comentario_devolucion`, `estado`) VALUES ('2', '2', '3', '4', '2025-11-24', '07:10:00', '09:30:00', '2025-11-25', NULL, 'Devuelto');
INSERT INTO `prestamos` (`id_prestamo`, `id_usuario`, `id_equipo`, `id_aula`, `fecha_prestamo`, `hora_inicio`, `hora_fin`, `fecha_devolucion`, `comentario_devolucion`, `estado`) VALUES ('3', '2', '4', '4', '2025-11-24', '07:10:00', '09:30:00', '2025-11-25', NULL, 'Devuelto');

-- Tabla: reservas
DROP TABLE IF EXISTS `reservas`;
CREATE TABLE `reservas` (
  `id_reserva` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  KEY `fk_reservas_aula` (`id_aula`),
  KEY `idx_reservas_usuario_fecha` (`id_usuario`,`fecha`,`hora_inicio`),
  CONSTRAINT `fk_reservas_aula` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON DELETE SET NULL,
  CONSTRAINT `fk_reservas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de reservas
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('1', '2', '1', '2025-11-21', '06:45:00', '07:55:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('2', '2', '1', '2025-11-21', '15:15:00', '17:50:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('3', '2', '1', '2025-11-21', '13:00:00', '13:45:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('4', '2', '1', '2025-11-21', '17:50:00', '18:35:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('5', '2', '1', '2025-11-21', '07:55:00', '09:25:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('6', '2', '1', '2025-11-21', '09:25:00', '10:10:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('7', '2', '1', '2025-11-22', '07:10:00', '08:40:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('8', '2', '1', '2025-11-22', '08:40:00', '10:10:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('9', '2', '1', '2025-11-22', '10:30:00', '11:15:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('10', '2', '1', '2025-11-22', '11:15:00', '12:45:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('11', '2', '1', '2025-11-26', '06:00:00', '07:10:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('12', '2', '1', '2025-11-26', '07:10:00', '09:25:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('13', '2', '1', '2025-11-26', '09:25:00', '10:10:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('14', '2', '1', '2025-11-26', '10:30:00', '12:00:00');
INSERT INTO `reservas` (`id_reserva`, `id_usuario`, `id_aula`, `fecha`, `hora_inicio`, `hora_fin`) VALUES ('15', '2', '1', '2025-11-26', '12:00:00', '12:45:00');

-- Tabla: reservas_canceladas
DROP TABLE IF EXISTS `reservas_canceladas`;
CREATE TABLE `reservas_canceladas` (
  `id_cancelacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_reserva` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `fecha_cancelacion` datetime DEFAULT current_timestamp(),
  `id_aula` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  PRIMARY KEY (`id_cancelacion`),
  KEY `fk_cancel_reserva` (`id_reserva`),
  KEY `fk_cancel_usuario` (`id_usuario`),
  KEY `fk_cancel_aula` (`id_aula`),
  CONSTRAINT `fk_cancel_aula` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON DELETE SET NULL,
  CONSTRAINT `fk_cancel_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE SET NULL,
  CONSTRAINT `fk_cancel_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: tipos_equipo
DROP TABLE IF EXISTS `tipos_equipo`;
CREATE TABLE `tipos_equipo` (
  `id_tipo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id_tipo`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de tipos_equipo
INSERT INTO `tipos_equipo` (`id_tipo`, `nombre`) VALUES ('3', 'EXTENSION');
INSERT INTO `tipos_equipo` (`id_tipo`, `nombre`) VALUES ('1', 'LAPTOP');
INSERT INTO `tipos_equipo` (`id_tipo`, `nombre`) VALUES ('4', 'PARLANTE');
INSERT INTO `tipos_equipo` (`id_tipo`, `nombre`) VALUES ('2', 'PROYECTOR');

-- Tabla: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `tipo_usuario` enum('Administrador','Profesor','Encargado') NOT NULL DEFAULT 'Profesor',
  `verificado` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `login_token` varchar(255) DEFAULT NULL,
  `login_expira` datetime DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `telefono_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `telefono_verificado_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de usuarios
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contraseña`, `tipo_usuario`, `verificado`, `verification_token`, `token_expira`, `reset_token`, `reset_expira`, `activo`, `login_token`, `login_expira`, `telefono`, `telefono_verificado`, `telefono_verificado_at`) VALUES ('1', 'Edson Braga Cabrera', 'edsonbragacabrera@gmail.com', '$2y$10$nIOSsJxtiaX20fntxEleR.CgSRhnapx/wIGf0VXDj9xLryElBVAFa', 'Administrador', '1', NULL, NULL, NULL, NULL, '1', NULL, NULL, '985463217', '0', NULL);
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contraseña`, `tipo_usuario`, `verificado`, `verification_token`, `token_expira`, `reset_token`, `reset_expira`, `activo`, `login_token`, `login_expira`, `telefono`, `telefono_verificado`, `telefono_verificado_at`) VALUES ('2', 'Alberto Braga Cabrera', 'albertobragacabrera@gmail.com', '$2y$10$9ghZ5geBKzknrvpqY87izekKJdX8cMXtG.jzSAoxkv1h0gdeIMBoa', 'Profesor', '1', NULL, NULL, NULL, NULL, '1', NULL, NULL, '912558038', '0', NULL);
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contraseña`, `tipo_usuario`, `verificado`, `verification_token`, `token_expira`, `reset_token`, `reset_expira`, `activo`, `login_token`, `login_expira`, `telefono`, `telefono_verificado`, `telefono_verificado_at`) VALUES ('3', 'Santos Braga Cabrera', 'santosbragacabrera@gmail.com', '$2y$10$8QA79L3HCEkPYkgfOZDDn.D4ohmL0kCZ5Aj3OREuq/tQW5u/Jxc1y', 'Encargado', '1', NULL, NULL, NULL, NULL, '0', NULL, NULL, '958463217', '0', NULL);
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contraseña`, `tipo_usuario`, `verificado`, `verification_token`, `token_expira`, `reset_token`, `reset_expira`, `activo`, `login_token`, `login_expira`, `telefono`, `telefono_verificado`, `telefono_verificado_at`) VALUES ('4', 'Ingeniero', 'ing150655@gmail.com', '$2y$10$SRiXZ6QBYP3PCNOCNznoyeUbfRgXE7yDcQWACu43Thkb/psYYJCNK', 'Encargado', '1', NULL, NULL, NULL, NULL, '1', NULL, NULL, '954863217', '0', NULL);

-- Tabla: verification_codes
DROP TABLE IF EXISTS `verification_codes`;
CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `action_type` enum('reserva','prestamo','cambio_clave') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vc_user` (`user_id`),
  KEY `idx_vc_action` (`action_type`),
  KEY `idx_vc_expires` (`expires_at`),
  KEY `idx_vc_used` (`used`),
  CONSTRAINT `verification_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Datos de verification_codes
INSERT INTO `verification_codes` (`id`, `user_id`, `code`, `action_type`, `expires_at`, `used`, `created_at`) VALUES ('11', '2', '568887', 'reserva', '2025-11-25 14:53:44', '1', '2025-11-25 08:43:44');
INSERT INTO `verification_codes` (`id`, `user_id`, `code`, `action_type`, `expires_at`, `used`, `created_at`) VALUES ('12', '2', '640905', 'prestamo', '2025-11-25 14:54:06', '1', '2025-11-25 08:44:06');
INSERT INTO `verification_codes` (`id`, `user_id`, `code`, `action_type`, `expires_at`, `used`, `created_at`) VALUES ('13', '2', '164827', 'reserva', '2025-11-25 15:36:27', '1', '2025-11-25 09:26:27');
INSERT INTO `verification_codes` (`id`, `user_id`, `code`, `action_type`, `expires_at`, `used`, `created_at`) VALUES ('14', '2', '040164', 'prestamo', '2025-11-25 15:36:53', '1', '2025-11-25 09:26:53');

SET FOREIGN_KEY_CHECKS=1;
