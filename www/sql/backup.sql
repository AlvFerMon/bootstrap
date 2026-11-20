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


-- =====================================================================
--  DATOS (al menos 10 registros por tabla)
-- =====================================================================

-- Tablas auxiliares
INSERT INTO tipos_socio (nombre, descripcion, cuota_mensual, descuento_pct, activo, fecha_creacion, imagen) VALUES
('Básico',      'Recordatorios de vacunas por correo',                    0.00,  0.00, 1, '2022-01-10', 'img/tipos_socio/1.png'),
('Bronce',      'Revisión anual gratuita',                                5.00,  5.00, 1, '2022-01-10', 'img/tipos_socio/2.png'),
('Plata',       'Revisión anual y vacunas con descuento',                 9.90, 10.00, 1, '2022-01-10', 'img/tipos_socio/3.png'),
('Oro',         'Todas las consultas con descuento y urgencias sin recargo', 14.90, 15.00, 1, '2022-01-10', 'img/tipos_socio/4.png'),
('Platino',     'Plan Oro más peluquería mensual',                       24.90, 20.00, 1, '2023-03-01', 'img/tipos_socio/5.png'),
('Familiar',    'Hasta cuatro mascotas en el mismo plan',                19.90, 12.00, 1, '2023-03-01', 'img/tipos_socio/6.png'),
('Joven',       'Para propietarios menores de 26 años',                   3.90,  8.00, 1, '2023-09-15', 'img/tipos_socio/7.png'),
('Senior',      'Para propietarios mayores de 65 años',                   3.90, 10.00, 1, '2023-09-15', 'img/tipos_socio/8.png'),
('Protectora',  'Convenio con protectoras de animales',                   0.00, 25.00, 1, '2024-02-01', 'img/tipos_socio/9.png'),
('Granja',      'Para explotaciones ganaderas',                          39.90, 15.00, 0, '2024-06-20', 'img/tipos_socio/10.png');

INSERT INTO especies (nombre, esperanza_vida_anios, es_exotica, fecha_alta, imagen) VALUES
('Perro',      13, 0, '2022-01-10', 'img/especies/1.png'),
('Gato',       15, 0, '2022-01-10', 'img/especies/2.png'),
('Conejo',      9, 0, '2022-01-10', 'img/especies/3.png'),
('Hámster',     3, 0, '2022-01-10', 'img/especies/4.png'),
('Periquito',  10, 1, '2022-05-04', 'img/especies/5.png'),
('Loro',       50, 1, '2022-05-04', 'img/especies/6.png'),
('Tortuga',    40, 1, '2022-05-04', 'img/especies/7.png'),
('Hurón',       8, 1, '2023-02-11', 'img/especies/8.png'),
('Caballo',    28, 0, '2023-06-01', 'img/especies/9.png'),
('Vaca',       20, 0, '2023-06-01', 'img/especies/10.png');

INSERT INTO especialidades (nombre, descripcion, recargo_consulta, requiere_quirofano, fecha_alta, imagen) VALUES
('Medicina general',    'Consultas, vacunas y revisiones',             0.00, 0, '2022-01-10', 'img/especialidades/1.png'),
('Cirugía',             'Intervenciones quirúrgicas',                 25.00, 1, '2022-01-10', 'img/especialidades/2.png'),
('Dermatología',        'Piel, pelo y alergias',                      10.00, 0, '2022-01-10', 'img/especialidades/3.png'),
('Cardiología',         'Corazón y aparato circulatorio',             20.00, 0, '2022-03-15', 'img/especialidades/4.png'),
('Traumatología',       'Huesos, articulaciones y lesiones',          15.00, 1, '2022-03-15', 'img/especialidades/5.png'),
('Oftalmología',        'Ojos y visión',                              15.00, 1, '2022-09-01', 'img/especialidades/6.png'),
('Odontología',         'Dientes y boca',                             10.00, 1, '2022-09-01', 'img/especialidades/7.png'),
('Animales exóticos',   'Aves, reptiles y pequeños mamíferos',        12.00, 0, '2023-02-11', 'img/especialidades/8.png'),
('Animales de granja',  'Ganado y caballos',                          18.00, 0, '2023-06-01', 'img/especialidades/9.png'),
('Diagnóstico por imagen', 'Radiografías, ecografías y TAC',           20.00, 0, '2024-01-08', 'img/especialidades/10.png');

INSERT INTO servicios (nombre, descripcion, precio_base, duracion_min, requiere_ayuno, fecha_alta, imagen) VALUES
('Consulta general',     'Exploración física completa',                    35.00,  30, 0, '2022-01-10', 'img/servicios/1.png'),
('Vacunación',           'Vacunas obligatorias y recomendadas',            30.00,  15, 0, '2022-01-10', 'img/servicios/2.png'),
('Desparasitación',      'Tratamiento interno y externo',                  20.00,  15, 0, '2022-01-10', 'img/servicios/3.png'),
('Análisis de sangre',   'Hemograma y bioquímica',                         55.00,  20, 1, '2022-01-10', 'img/servicios/4.png'),
('Radiografía',          'Radiología digital',                             60.00,  30, 0, '2022-03-15', 'img/servicios/5.png'),
('Ecografía',            'Ecografía abdominal o cardiaca',                 70.00,  40, 1, '2022-03-15', 'img/servicios/6.png'),
('Esterilización',       'Cirugía con anestesia general',                 150.00, 120, 1, '2022-03-15', 'img/servicios/7.png'),
('Limpieza dental',      'Limpieza con ultrasonidos bajo sedación',        80.00,  60, 1, '2022-09-01', 'img/servicios/8.png'),
('Peluquería',           'Baño, corte de pelo y uñas',                     30.00,  60, 0, '2023-01-20', 'img/servicios/9.png'),
('Urgencia',             'Atención inmediata a cualquier hora',            70.00,  45, 0, '2023-01-20', 'img/servicios/10.png');

-- Tablas principales
INSERT INTO propietarios (nombre, apellidos, dni, telefono, email, direccion, saldo_pendiente, acepta_publicidad, fecha_alta, foto, tipo_socio_id) VALUES
('Lucía', 'García Romero', '45781236N', '612345678', 'lucia.garcia@correo.es', 'C/ Feria 12, Sevilla', 0.00, 1, '2022-02-14', 'img/propietarios/1.png', 4),
('Javier', 'Martín López', '28734519K', '623456789', 'javier.martin@correo.es', 'Av. de la Constitución 3, Sevilla', 35.50, 0, '2022-04-03', 'img/propietarios/2.png', 1),
('Carmen', 'Ruiz Delgado', '30918274H', '634567890', 'carmen.ruiz@correo.es', 'C/ Betis 45, Sevilla', 0.00, 1, '2022-06-21', 'img/propietarios/3.png', 6),
('Antonio', 'Sánchez Vega', '52147893Q', '645678901', 'antonio.sanchez@correo.es', 'C/ Sierpes 8, Sevilla', 120.00, 0, '2022-09-10', 'img/propietarios/4.png', NULL),
('María', 'Fernández Gil', '77412658V', '656789012', 'maria.fernandez@correo.es', 'Pza. del Altozano 2, Sevilla', 0.00, 1, '2023-01-15', 'img/propietarios/5.png', 3),
('Pablo', 'Moreno Castillo', '49638217P', '667890123', 'pablo.moreno@correo.es', 'C/ San Jacinto 30, Sevilla', 15.00, 1, '2023-03-08', 'img/propietarios/6.png', 7),
('Elena', 'Jiménez Ortega', '26381947G', '678901234', 'elena.jimenez@correo.es', 'Av. Kansas City 101, Sevilla', 0.00, 0, '2023-05-19', 'img/propietarios/7.png', 2),
('Manuel', 'Navarro Prieto', '31275486V', '689012345', 'manuel.navarro@correo.es', 'C/ Asunción 54, Sevilla', 60.00, 0, '2023-07-02', 'img/propietarios/8.png', 8),
('Rocío', 'Domínguez Peña', '48596321N', '690123456', 'rocio.dominguez@correo.es', 'C/ Arjona 7, Sevilla', 0.00, 1, '2023-10-11', 'img/propietarios/9.png', 9),
('Francisco', 'Romero Marín', '53962874K', '601234567', 'francisco.romero@correo.es', 'Ctra. de Utrera km 4, Dos Hermanas', 210.75, 0, '2024-01-25', 'img/propietarios/10.png', 10),
('Laura', 'Herrera Campos', '29481736Z', '611223344', 'laura.herrera@correo.es', 'C/ Luis Montoto 88, Sevilla', 0.00, 1, '2024-04-09', 'img/propietarios/11.png', 5),
('Sergio', 'Molina Rubio', '47852163L', '622334455', NULL, 'C/ Pureza 19, Sevilla', 0.00, 0, '2024-08-30', 'img/propietarios/12.png', NULL);

INSERT INTO veterinarios (nombre, apellidos, num_colegiado, telefono, email, anios_experiencia, hace_urgencias, fecha_contratacion, foto, especialidad_id) VALUES
('Carlos', 'Pérez Alonso', 'SE-1024', '954110001', 'carlos.perez@carlitosysnoopy.es', 18, 1, '2015-09-01', 'img/veterinarios/1.png', 1),
('Ana', 'Gómez Soto', 'SE-1188', '954110002', 'ana.gomez@carlitosysnoopy.es', 12, 1, '2017-02-15', 'img/veterinarios/2.png', 2),
('David', 'Torres Ramos', 'SE-1342', '954110003', 'david.torres@carlitosysnoopy.es', 9, 0, '2018-06-01', 'img/veterinarios/3.png', 3),
('Marta', 'Vázquez Lara', 'SE-1476', '954110004', 'marta.vazquez@carlitosysnoopy.es', 7, 0, '2019-10-01', 'img/veterinarios/4.png', 4),
('Raúl', 'Castro Medina', 'SE-1590', '954110005', 'raul.castro@carlitosysnoopy.es', 6, 1, '2020-03-16', 'img/veterinarios/5.png', 5),
('Irene', 'Blanco Nieto', 'SE-1655', '954110006', 'irene.blanco@carlitosysnoopy.es', 5, 0, '2021-01-11', 'img/veterinarios/6.png', 6),
('Alberto', 'Rojas Fuentes', 'SE-1721', '954110007', NULL, 4, 1, '2021-09-06', 'img/veterinarios/7.png', 7),
('Nuria', 'Iglesias Cano', 'SE-1803', '954110008', 'nuria.iglesias@carlitosysnoopy.es', 3, 0, '2022-05-02', 'img/veterinarios/8.png', 8),
('Hugo', 'Ortiz Serrano', 'SE-1877', '954110009', 'hugo.ortiz@carlitosysnoopy.es', 2, 1, '2023-06-19', 'img/veterinarios/9.png', 9),
('Paula', 'Santos Rey', 'SE-1950', '954110010', 'paula.santos@carlitosysnoopy.es', 1, 0, '2024-09-02', 'img/veterinarios/10.png', 10);

INSERT INTO mascotas (nombre, raza, num_chip, peso_kg, vacunada, esterilizada, fecha_nacimiento, foto, especie_id, propietario_id) VALUES
('Snoopy', 'Beagle', '941000024681357', 12.40, 1, 1, '2018-05-12', 'img/mascotas/1.png', 1, 1),
('Carlitos', 'Europeo común', '941000024681358', 4.80, 1, 1, '2019-08-03', 'img/mascotas/2.png', 2, 1),
('Toby', 'Labrador', '941000024681359', 31.20, 1, 0, '2020-02-20', 'img/mascotas/3.png', 1, 2),
('Luna', 'Siamés', '941000024681360', 3.90, 1, 1, '2021-04-11', 'img/mascotas/4.png', 2, 3),
('Tambor', 'Belier', NULL, 2.10, 1, 0, '2022-07-01', 'img/mascotas/5.png', 3, 3),
('Kiwi', 'Ondulado', NULL, 0.04, 0, 0, '2023-03-15', 'img/mascotas/6.png', 5, 4),
('Rocky', 'Bulldog francés', '941000024681361', 11.70, 1, 1, '2019-11-30', 'img/mascotas/7.png', 1, 5),
('Nala', 'Persa', '941000024681362', 4.30, 0, 0, '2022-09-09', 'img/mascotas/8.png', 2, 6),
('Pipo', 'Ruso', NULL, 0.05, 0, 0, '2024-01-20', 'img/mascotas/9.png', 4, 6),
('Coco', 'Yaco', NULL, 0.45, 1, 0, '2010-06-05', 'img/mascotas/10.png', 6, 7),
('Manolita', 'Mediterránea', NULL, 1.80, 0, 0, '2005-04-22', 'img/mascotas/11.png', 7, 8),
('Bandido', 'Albino', NULL, 1.20, 1, 1, '2022-12-12', 'img/mascotas/12.png', 8, 9),
('Relámpago', 'Pura raza española', '941000024681363', 480.00, 1, 0, '2014-03-18', 'img/mascotas/13.png', 9, 10),
('Margarita', 'Frisona', '941000024681364', 620.00, 1, 0, '2017-05-27', 'img/mascotas/14.png', 10, 10),
('Thor', 'Pastor alemán', '941000024681365', 34.50, 1, 0, '2021-10-08', 'img/mascotas/15.png', 1, 11);

INSERT INTO citas (fecha_hora, motivo, observaciones, precio_final, atendida, imagen, mascota_id, veterinario_id, servicio_id) VALUES
('2025-01-14 10:00:00', 'Revisión anual', 'Todo correcto. Próxima revisión en un año.', 29.75, 1, 'img/citas/1.png', 1, 1, 1),
('2025-01-20 11:30:00', 'Vacuna de la rabia', NULL, 25.50, 1, 'img/citas/2.png', 3, 1, 2),
('2025-02-03 09:15:00', 'Picores y caída de pelo', 'Posible dermatitis alérgica. Champú y dieta.', 45.00, 1, 'img/citas/3.png', 4, 3, 1),
('2025-02-17 16:00:00', 'Esterilización', 'Ayuno de 12 horas cumplido. Sin complicaciones.', 175.00, 1, 'img/citas/4.png', 2, 2, 7),
('2025-03-05 12:00:00', 'Soplo cardiaco', 'Ecocardiografía de control en seis meses.', 90.00, 1, 'img/citas/5.png', 7, 4, 6),
('2025-03-22 10:45:00', 'Cojera en pata trasera', 'Radiografía: esguince leve. Reposo.', 75.00, 1, 'img/citas/6.png', 15, 5, 5),
('2025-04-08 17:30:00', 'Ojo lloroso', 'Conjuntivitis. Colirio cada 8 horas.', 50.00, 1, 'img/citas/7.png', 8, 6, 1),
('2025-04-25 09:00:00', 'Limpieza de boca', 'Se extraen dos piezas.', 90.00, 1, 'img/citas/8.png', 10, 7, 8),
('2025-05-12 13:15:00', 'Control de caparazón', NULL, 47.00, 1, 'img/citas/9.png', 11, 8, 1),
('2025-06-02 08:30:00', 'Revisión de cascos', 'Visita a domicilio en la finca.', 88.00, 1, 'img/citas/10.png', 13, 9, 1),
('2025-06-18 10:00:00', 'Análisis de sangre', 'Valores renales algo altos. Repetir en 3 meses.', 49.50, 1, 'img/citas/11.png', 1, 1, 4),
('2025-07-09 19:45:00', 'Urgencia: ingesta de chocolate', 'Se induce el vómito. Evoluciona bien.', 70.00, 1, 'img/citas/12.png', 3, 2, 10),
('2025-09-15 11:00:00', 'Peluquería completa', NULL, 27.00, 1, 'img/citas/13.png', 7, 1, 9),
('2025-10-01 12:30:00', 'Desparasitación', NULL, 17.00, 1, 'img/citas/14.png', 12, 8, 3),
('2025-11-20 09:45:00', 'Vacuna triple felina', NULL, 27.00, 1, 'img/citas/15.png', 8, 1, 2),
('2026-01-13 16:30:00', 'Control de peso', 'Bajar ración un 10 %.', 31.50, 1, 'img/citas/16.png', 5, 8, 1),
('2026-03-04 10:15:00', 'Radiografía de tórax', NULL, 80.00, 1, 'img/citas/17.png', 14, 10, 5),
('2026-12-02 10:00:00', 'Revisión anual', NULL, 29.75, 0, 'img/citas/18.png', 2, 1, 1),
('2026-12-09 12:00:00', 'Ecografía abdominal', 'Venir en ayunas.', 90.00, 0, 'img/citas/19.png', 4, 10, 6),
('2026-12-16 17:00:00', 'Primera vacuna', NULL, 30.00, 0, 'img/citas/20.png', 9, 8, 2);

SET FOREIGN_KEY_CHECKS = 1;
