-- =====================================================================
--  Clínica veterinaria Carlitos & Snoopy
--  Copia de seguridad: creación de la base de datos, tablas, relaciones
--  y datos de ejemplo (10 registros por tabla).
-- =====================================================================
--
--  Relaciones (todas 1:N):
--    propietarios 1 ──< N mascotas
--    mascotas     1 ──< N citas
--    veterinarios 1 ──< N citas
--
--  Cada tabla tiene: PK entera autoincrementada, un número que no es
--  PK ni FK, una cadena, un booleano, una fecha y una imagen.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS clinica_veterinaria;
CREATE DATABASE clinica_veterinaria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE clinica_veterinaria;

CREATE TABLE propietarios (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(50)   NOT NULL,
  apellidos     VARCHAR(100)  NOT NULL,
  dni           CHAR(9)       NOT NULL,
  telefono      VARCHAR(15)   NOT NULL,
  email         VARCHAR(100)  NULL,
  descuento     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,   -- número (%)
  es_socio      BOOLEAN       NOT NULL DEFAULT FALSE,  -- booleano
  fecha_alta    DATE          NOT NULL,                -- fecha
  foto          VARCHAR(255)  NULL,                    -- imagen
  PRIMARY KEY (id),
  UNIQUE KEY uk_propietarios_dni (dni)
) ENGINE=InnoDB;

CREATE TABLE veterinarios (
  id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(50)   NOT NULL,
  apellidos           VARCHAR(100)  NOT NULL,
  num_colegiado       VARCHAR(20)   NOT NULL,
  especialidad        VARCHAR(60)   NOT NULL,
  anios_experiencia   TINYINT UNSIGNED NOT NULL DEFAULT 0, -- número
  hace_urgencias      BOOLEAN       NOT NULL DEFAULT FALSE, -- booleano
  fecha_contratacion  DATE          NOT NULL,               -- fecha
  foto                VARCHAR(255)  NULL,                   -- imagen
  PRIMARY KEY (id),
  UNIQUE KEY uk_veterinarios_colegiado (num_colegiado)
) ENGINE=InnoDB;

CREATE TABLE mascotas (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre            VARCHAR(50)   NOT NULL,
  especie           VARCHAR(30)   NOT NULL,
  raza              VARCHAR(50)   NULL,
  peso_kg           DECIMAL(6,2)  NOT NULL,                -- número
  vacunada          BOOLEAN       NOT NULL DEFAULT FALSE,  -- booleano
  fecha_nacimiento  DATE          NOT NULL,                -- fecha
  foto              VARCHAR(255)  NULL,                    -- imagen
  propietario_id    INT UNSIGNED  NOT NULL,
  PRIMARY KEY (id),
  KEY idx_mascotas_propietario (propietario_id),
  CONSTRAINT fk_mascotas_propietario
    FOREIGN KEY (propietario_id) REFERENCES propietarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE citas (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  fecha_hora      DATETIME      NOT NULL,                -- fecha
  motivo          VARCHAR(150)  NOT NULL,                -- cadena
  precio          DECIMAL(7,2)  NOT NULL DEFAULT 0.00,   -- número
  atendida        BOOLEAN       NOT NULL DEFAULT FALSE,  -- booleano
  imagen          VARCHAR(255)  NULL,                    -- imagen (informe / radiografía)
  mascota_id      INT UNSIGNED  NOT NULL,
  veterinario_id  INT UNSIGNED  NOT NULL,
  PRIMARY KEY (id),
  KEY idx_citas_mascota (mascota_id),
  KEY idx_citas_veterinario (veterinario_id),
  CONSTRAINT fk_citas_mascota
    FOREIGN KEY (mascota_id) REFERENCES mascotas (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_citas_veterinario
    FOREIGN KEY (veterinario_id) REFERENCES veterinarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
