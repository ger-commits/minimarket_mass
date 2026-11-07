-- Migración 006: escáner de celular del CAJERO (cable USB), guardado en la base de datos.
-- Crea dos tablas nuevas. No modifica ninguna tabla existente y no toca las del escáner del almacén.
-- Puede ejecutarse más de una vez. Requiere MySQL 8.0 o superior.
USE minimarket_mass;

-- Un enlace (QR) vigente por caja. Al generar uno nuevo, el anterior deja de funcionar.
CREATE TABLE IF NOT EXISTS escaner_caja_vinculo (
    id_caja      SMALLINT UNSIGNED  NOT NULL,
    token        CHAR(32)           NOT NULL,
    id_empleado  MEDIUMINT UNSIGNED NOT NULL,            -- cajero que generó el enlace
    creado       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visto        DATETIME           NULL,                 -- última vez que el celular avisó
    seq           INT UNSIGNED       NOT NULL DEFAULT 0,   -- número del último código recibido
    CONSTRAINT pk_escaner_caja_vinculo PRIMARY KEY (id_caja),
    CONSTRAINT fk_escaner_caja_vinculo_caja FOREIGN KEY (id_caja) REFERENCES caja (id_caja),
    CONSTRAINT fk_escaner_caja_vinculo_empleado FOREIGN KEY (id_empleado) REFERENCES empleado (id_empleado)
) ENGINE=InnoDB;

-- Códigos leídos por el celular y aún no agregados a la venta.
CREATE TABLE IF NOT EXISTS escaner_caja_codigo (
    id_escaner_caja_codigo INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    id_caja                SMALLINT UNSIGNED NOT NULL,
    seq                    INT UNSIGNED      NOT NULL,
    codigo                 VARCHAR(40)       NOT NULL,
    hora                   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_escaner_caja_codigo PRIMARY KEY (id_escaner_caja_codigo),
    CONSTRAINT uq_escaner_caja_codigo UNIQUE (id_caja, seq),
    CONSTRAINT fk_escaner_caja_codigo_caja FOREIGN KEY (id_caja) REFERENCES caja (id_caja)
) ENGINE=InnoDB;
