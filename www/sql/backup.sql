-- =====================================================================
--  Clínica veterinaria Carlitos & Snoopy
--  Copia de seguridad: creación de la base de datos, tablas y relaciones.
-- =====================================================================
--
--  Tablas auxiliares (catálogos para los desplegables):
--    tipos_socio, especies, especialidades, servicios
--
--  Tablas principales:
--    propietarios, mascotas, veterinarios, citas
--
--  Relaciones (todas 1:N):
--    tipos_socio    1 ──< N propietarios
--    propietarios   1 ──< N mascotas
--    especies       1 ──< N mascotas
--    especialidades 1 ──< N veterinarios
--    mascotas       1 ──< N citas
--    veterinarios   1 ──< N citas
--    servicios      1 ──< N citas
--
--  Todas las tablas tienen:
--    - PK entera autoincrementada
--    - un número que no es PK ni FK
--    - una cadena de texto
--    - un booleano (TINYINT(1) que solo admite 0 o 1)
--    - una fecha
--    - una imagen (ruta del archivo)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS clinica_veterinaria;
CREATE DATABASE clinica_veterinaria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE clinica_veterinaria;

CREATE TABLE tipos_socio (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(40)   NOT NULL,
  descripcion     VARCHAR(255)  NULL,
  cuota_mensual   DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  descuento_pct   DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  fecha_creacion  DATE          NOT NULL,
  imagen          VARCHAR(255)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_tipos_socio_nombre (nombre),
  CONSTRAINT ck_tipos_socio_activo   CHECK (activo IN (0, 1)),
  CONSTRAINT ck_tipos_socio_cuota    CHECK (cuota_mensual >= 0),
  CONSTRAINT ck_tipos_socio_desc     CHECK (descuento_pct BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE especies (
  id                    INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  nombre                VARCHAR(40)       NOT NULL,
  esperanza_vida_anios  TINYINT UNSIGNED  NOT NULL,
  es_exotica            TINYINT(1)        NOT NULL DEFAULT 0,
  fecha_alta            DATE              NOT NULL,
  imagen                VARCHAR(255)      NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_especies_nombre (nombre),
  CONSTRAINT ck_especies_exotica CHECK (es_exotica IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE especialidades (
  id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(60)   NOT NULL,
  descripcion         VARCHAR(255)  NULL,
  recargo_consulta    DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
  requiere_quirofano  TINYINT(1)    NOT NULL DEFAULT 0,
  fecha_alta          DATE          NOT NULL,
  imagen              VARCHAR(255)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_especialidades_nombre (nombre),
  CONSTRAINT ck_especialidades_quirofano CHECK (requiere_quirofano IN (0, 1)),
  CONSTRAINT ck_especialidades_recargo   CHECK (recargo_consulta >= 0)
) ENGINE=InnoDB;

CREATE TABLE servicios (
  id              INT UNSIGNED       NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(80)        NOT NULL,
  descripcion     VARCHAR(255)       NULL,
  precio_base     DECIMAL(7,2)       NOT NULL,
  duracion_min    SMALLINT UNSIGNED  NOT NULL,
  requiere_ayuno  TINYINT(1)         NOT NULL DEFAULT 0,
  fecha_alta      DATE               NOT NULL,
  imagen          VARCHAR(255)       NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_servicios_nombre (nombre),
  CONSTRAINT ck_servicios_ayuno  CHECK (requiere_ayuno IN (0, 1)),
  CONSTRAINT ck_servicios_precio CHECK (precio_base >= 0)
) ENGINE=InnoDB;

CREATE TABLE propietarios (
  id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre             VARCHAR(50)   NOT NULL,
  apellidos          VARCHAR(100)  NOT NULL,
  dni                CHAR(9)       NOT NULL,
  telefono           VARCHAR(15)   NOT NULL,
  email              VARCHAR(100)  NULL,
  direccion          VARCHAR(150)  NULL,
  saldo_pendiente    DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  acepta_publicidad  TINYINT(1)    NOT NULL DEFAULT 0,
  fecha_alta         DATE          NOT NULL,
  foto               VARCHAR(255)  NULL,
  tipo_socio_id      INT UNSIGNED  NULL,          -- NULL = no es socio
  PRIMARY KEY (id),
  UNIQUE KEY uk_propietarios_dni (dni),
  UNIQUE KEY uk_propietarios_email (email),
  KEY idx_propietarios_tipo_socio (tipo_socio_id),
  CONSTRAINT ck_propietarios_publicidad CHECK (acepta_publicidad IN (0, 1)),
  CONSTRAINT fk_propietarios_tipo_socio
    FOREIGN KEY (tipo_socio_id) REFERENCES tipos_socio (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE veterinarios (
  id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(50)       NOT NULL,
  apellidos           VARCHAR(100)      NOT NULL,
  num_colegiado       VARCHAR(20)       NOT NULL,
  telefono            VARCHAR(15)       NULL,
  email               VARCHAR(100)      NULL,
  anios_experiencia   TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  hace_urgencias      TINYINT(1)        NOT NULL DEFAULT 0,
  fecha_contratacion  DATE              NOT NULL,
  foto                VARCHAR(255)      NULL,
  especialidad_id     INT UNSIGNED      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_veterinarios_colegiado (num_colegiado),
  KEY idx_veterinarios_especialidad (especialidad_id),
  CONSTRAINT ck_veterinarios_urgencias CHECK (hace_urgencias IN (0, 1)),
  CONSTRAINT fk_veterinarios_especialidad
    FOREIGN KEY (especialidad_id) REFERENCES especialidades (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE mascotas (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre            VARCHAR(50)   NOT NULL,
  raza              VARCHAR(50)   NULL,
  num_chip          VARCHAR(15)   NULL,
  peso_kg           DECIMAL(6,2)  NOT NULL,
  vacunada          TINYINT(1)    NOT NULL DEFAULT 0,
  esterilizada      TINYINT(1)    NOT NULL DEFAULT 0,
  fecha_nacimiento  DATE          NOT NULL,
  foto              VARCHAR(255)  NULL,
  especie_id        INT UNSIGNED  NOT NULL,
  propietario_id    INT UNSIGNED  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mascotas_chip (num_chip),
  KEY idx_mascotas_especie (especie_id),
  KEY idx_mascotas_propietario (propietario_id),
  CONSTRAINT ck_mascotas_vacunada     CHECK (vacunada IN (0, 1)),
  CONSTRAINT ck_mascotas_esterilizada CHECK (esterilizada IN (0, 1)),
  CONSTRAINT ck_mascotas_peso         CHECK (peso_kg > 0),
  CONSTRAINT fk_mascotas_especie
    FOREIGN KEY (especie_id) REFERENCES especies (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mascotas_propietario
    FOREIGN KEY (propietario_id) REFERENCES propietarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE citas (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  fecha_hora      DATETIME      NOT NULL,
  motivo          VARCHAR(150)  NOT NULL,
  observaciones   TEXT          NULL,
  precio_final    DECIMAL(7,2)  NOT NULL DEFAULT 0.00,
  atendida        TINYINT(1)    NOT NULL DEFAULT 0,
  imagen          VARCHAR(255)  NULL,             -- informe, radiografía…
  mascota_id      INT UNSIGNED  NOT NULL,
  veterinario_id  INT UNSIGNED  NOT NULL,
  servicio_id     INT UNSIGNED  NOT NULL,
  PRIMARY KEY (id),
  KEY idx_citas_fecha (fecha_hora),
  KEY idx_citas_mascota (mascota_id),
  KEY idx_citas_veterinario (veterinario_id),
  KEY idx_citas_servicio (servicio_id),
  CONSTRAINT ck_citas_atendida CHECK (atendida IN (0, 1)),
  CONSTRAINT ck_citas_precio   CHECK (precio_final >= 0),
  CONSTRAINT fk_citas_mascota
    FOREIGN KEY (mascota_id) REFERENCES mascotas (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_citas_veterinario
    FOREIGN KEY (veterinario_id) REFERENCES veterinarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_citas_servicio
    FOREIGN KEY (servicio_id) REFERENCES servicios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;