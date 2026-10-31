
CREATE DATABASE IF NOT EXISTS cozy_study DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE cozy_study;


CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    edad TINYINT UNSIGNED NULL,
    password VARCHAR(255) NOT NULL,
    rol TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: General, 1: Administrador, 2: Moderador',
    activo TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0: Deshabilitado, 1: Activo',
    marcado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Sin marca, 1: Marcado por mal comportamiento (tarjeta amarilla)',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progreso_json LONGTEXT NULL COMMENT 'Progreso de juego del usuario (nivel, exp, avatar, etc.) en formato JSON. NULL = progreso por defecto (Nivel 1).'
) ENGINE=InnoDB;

-- Nota: si la base de datos ya existía (instalación previa) y la tabla "usuarios" no tiene
-- la columna "progreso_json", ejecutar el script database/migracion_progreso.sql para agregarla
-- y reiniciar el progreso de todos los usuarios a Nivel 1.
-- Nota: si la base de datos ya existía y la tabla "usuarios" no tiene la columna "marcado",
-- ejecutar el script database/migracion_marcado_usuario.sql para agregarla (tarjeta amarilla).


CREATE TABLE IF NOT EXISTS misiones (
    id_mision INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    texto_tarea VARCHAR(255) NOT NULL,
    estado_completado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Pendiente, 1: Completada',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_mision FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS contactos (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_remitente VARCHAR(50) NOT NULL,
    email_remitente VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    fecha DATE NOT NULL,
    recordatorios VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Dias de aviso separados por coma, ej: 7,3,1,0',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_evento FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS jugadores_online (
    id_usuario INT PRIMARY KEY,
    id_sala INT NULL,
    nombre_usuario VARCHAR(30) NOT NULL,
    pos_x FLOAT NOT NULL DEFAULT 200,
    pos_y FLOAT NOT NULL DEFAULT 100,
    direccion VARCHAR(5) NOT NULL DEFAULT 'right',
    estado VARCHAR(10) NOT NULL DEFAULT 'idle',
    avatar_base VARCHAR(30) NOT NULL DEFAULT 'copo',
    avatar_clothing VARCHAR(30) NOT NULL DEFAULT 'none',
    avatar_accessory VARCHAR(30) NOT NULL DEFAULT 'none',
    materia_estudio VARCHAR(80) NULL,
    tiempo_estudio_segundos INT UNSIGNED NOT NULL DEFAULT 0,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_online FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS chat_historial (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    rol ENUM('user','model') NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_chat FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS salas (
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    id_propietario INT NOT NULL,
    privacidad ENUM('publica','privada') NOT NULL DEFAULT 'privada',
    password_hash VARCHAR(255) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sala_propietario FOREIGN KEY (id_propietario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- La tabla jugadores_online se define antes que esta, así que la FK hacia
-- salas se agrega acá, una vez que "salas" ya existe.
ALTER TABLE jugadores_online
    ADD CONSTRAINT fk_jugador_sala
    FOREIGN KEY (id_sala) REFERENCES salas(id_sala) ON DELETE SET NULL;


CREATE TABLE IF NOT EXISTS sala_chat (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_sala INT NOT NULL,
    id_usuario INT NOT NULL,
    mensaje VARCHAR(500) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sala_chat_sala FOREIGN KEY (id_sala) REFERENCES salas(id_sala) ON DELETE CASCADE,
    CONSTRAINT fk_sala_chat_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =========================================
--   USUARIOS DE PRUEBA (credenciales conocidas para la defensa del proyecto)
-- =========================================
-- Admin      -> usuario: Profesor Admin | password: Admin123!
-- General 1  -> usuario: juan           | password: Estudiante123!
-- General 2  -> usuario: maria          | password: Estudiante123!
-- Las contraseñas están guardadas encriptadas con password_hash() (bcrypt).

INSERT INTO usuarios (nombre_usuario, email, password, rol, activo)
VALUES ('Profesor Admin', 'admin@cozystudy.com', '$2b$12$55ALcpVKo/zfIGgyYTPd4ejDDZLJRVZGBoYh8x7/Mprl6vmg6yCae', 1, 1);

INSERT INTO usuarios (nombre_usuario, email, password, rol, activo)
VALUES ('juan', 'juan@cozystudy.com', '$2b$12$WNYi.cHsYsysnE7K/w8ZG.gSbPdfpgKFcbR40TXXC38Xl.CVSolFe', 0, 1);

INSERT INTO usuarios (nombre_usuario, email, password, rol, activo)
VALUES ('maria', 'maria@cozystudy.com', '$2b$12$23A6zuu91akz7K8LpV7R2Osp1Eu2GKH/v/RaKRRCxJy3dTn68616e', 0, 1);
