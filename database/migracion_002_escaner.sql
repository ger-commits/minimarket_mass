-- Migración 002: escáner de celular del almacén.
-- Crea dos tablas nuevas. No modifica ninguna tabla existente.
-- Ejecutar una sola vez en la base minimarket_mass.

CREATE TABLE IF NOT EXISTS escaner_vinculo (
    token        CHAR(32)         NOT NULL,                 -- secreto del enlace/QR (aleatorio)
    id_empleado  MEDIUMINT UNSIGNED NOT NULL,               -- almacenero dueño del enlace
    creado       DATETIME         NOT NULL,
    expira       DATETIME         NOT NULL,                 -- 12 horas después de creado
    ultimo_uso   DATETIME         NULL,                     -- última vez que el celular habló con la PC
    CONSTRAINT pk_escaner_vinculo PRIMARY KEY (token),
    CONSTRAINT fk_escaner_vinculo_empleado FOREIGN KEY (id_empleado) REFERENCES empleado (id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS escaner_codigo (
    id_codigo    BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    token        CHAR(32)         NOT NULL,
    codigo       VARCHAR(48)      NOT NULL,                 -- código leído por el celular
    creado       DATETIME(3)      NOT NULL,
    CONSTRAINT pk_escaner_codigo PRIMARY KEY (id_codigo),
    CONSTRAINT fk_escaner_codigo_vinculo FOREIGN KEY (token) REFERENCES escaner_vinculo (token) ON DELETE CASCADE,
    KEY ix_escaner_codigo_token (token, id_codigo)
) ENGINE=InnoDB;
