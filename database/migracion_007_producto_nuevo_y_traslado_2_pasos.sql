-- Migración segura para una base minimarket_mass ya existente.
-- 1) Producto nuevo con autorización: el almacenero escanea un código que no existe, el administrador
--    de SU MISMA tienda lo permite o lo rechaza, y solo si lo permite el almacenero registra el producto.
-- 2) Traslado en dos pasos: el administrador de origen envía (el stock sale y queda EN_CAMINO) y el
--    almacenero de la tienda destino confirma lo que recibió (recién ahí entra el stock).
-- Los traslados que ya existían quedan como RECIBIDO. Puede ejecutarse más de una vez. Requiere MySQL 8.0.16+.
USE minimarket_mass;

-- Avisos para el almacen de cada tienda (si tu base aun no la tiene, por ejemplo si no corriste la migracion 004)
CREATE TABLE IF NOT EXISTS notificacion_almacen (
    id_notificacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_tienda       SMALLINT UNSIGNED NOT NULL,
    tipo            VARCHAR(30)       NOT NULL,
    mensaje         VARCHAR(255)      NOT NULL,
    creado          DATETIME          NOT NULL,
    CONSTRAINT pk_notificacion_almacen PRIMARY KEY (id_notificacion),
    CONSTRAINT fk_notificacion_tienda FOREIGN KEY (id_tienda) REFERENCES tienda (id_tienda),
    KEY ix_notificacion_tienda (id_tienda, id_notificacion)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 1) Solicitud de producto nuevo
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS solicitud_producto (
    id_solicitud_producto INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    codigo_barras         VARCHAR(14)        NOT NULL,
    estado                VARCHAR(10)        NOT NULL DEFAULT 'PENDIENTE',   -- PENDIENTE, APROBADA, RECHAZADA, USADA
    fecha_hora_solicitud  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_hora_respuesta  DATETIME           NULL,
    motivo_rechazo        VARCHAR(150)       NULL,
    id_tienda             SMALLINT UNSIGNED  NOT NULL,                       -- tienda del almacenero que escaneó
    id_empleado_solicita  MEDIUMINT UNSIGNED NOT NULL,                       -- almacenero
    id_empleado_responde  MEDIUMINT UNSIGNED NULL,                           -- administrador de la misma tienda
    id_producto           INT UNSIGNED       NULL,                           -- producto creado cuando la solicitud se usa
    CONSTRAINT pk_solicitud_producto PRIMARY KEY (id_solicitud_producto),
    CONSTRAINT ck_solprod_estado CHECK (estado IN ('PENDIENTE','APROBADA','RECHAZADA','USADA')),
    CONSTRAINT ck_solprod_codigo CHECK (codigo_barras REGEXP '^[0-9]{8,14}$'),
    CONSTRAINT fk_solprod_tienda    FOREIGN KEY (id_tienda)            REFERENCES tienda (id_tienda),
    CONSTRAINT fk_solprod_solicita  FOREIGN KEY (id_empleado_solicita) REFERENCES empleado (id_empleado),
    CONSTRAINT fk_solprod_responde  FOREIGN KEY (id_empleado_responde) REFERENCES empleado (id_empleado),
    CONSTRAINT fk_solprod_producto  FOREIGN KEY (id_producto)          REFERENCES producto (id_producto),
    KEY ix_solprod_tienda_estado (id_tienda, estado)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2) Traslado en dos pasos
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_migrar_traslado_2_pasos;
DELIMITER $$
CREATE PROCEDURE sp_migrar_traslado_2_pasos()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'traslado_mercaderia' AND column_name = 'estado'
    ) THEN
        ALTER TABLE traslado_mercaderia
            ADD COLUMN estado VARCHAR(10) NOT NULL DEFAULT 'RECIBIDO' AFTER observacion;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'traslado_mercaderia' AND column_name = 'fecha_recepcion'
    ) THEN
        ALTER TABLE traslado_mercaderia ADD COLUMN fecha_recepcion DATETIME NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'traslado_mercaderia' AND column_name = 'id_empleado_recibe'
    ) THEN
        ALTER TABLE traslado_mercaderia
            ADD COLUMN id_empleado_recibe MEDIUMINT UNSIGNED NULL,
            ADD CONSTRAINT fk_traslado_recibe FOREIGN KEY (id_empleado_recibe) REFERENCES empleado (id_empleado);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'traslado_mercaderia' AND column_name = 'observacion_recepcion'
    ) THEN
        ALTER TABLE traslado_mercaderia ADD COLUMN observacion_recepcion VARCHAR(200) NULL;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
         WHERE table_schema = DATABASE() AND table_name = 'traslado_mercaderia' AND constraint_name = 'ck_traslado_estado'
    ) THEN
        ALTER TABLE traslado_mercaderia
            ADD CONSTRAINT ck_traslado_estado CHECK (estado IN ('EN_CAMINO','RECIBIDO'));
    END IF;
END$$
DELIMITER ;

CALL sp_migrar_traslado_2_pasos();
DROP PROCEDURE sp_migrar_traslado_2_pasos;

-- Qué se envió en cada traslado (una fila por producto y lote) y qué se recibió.
CREATE TABLE IF NOT EXISTS traslado_detalle (
    id_detalle        INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    id_traslado       INT UNSIGNED      NOT NULL,
    id_producto       INT UNSIGNED      NOT NULL,
    codigo_lote       VARCHAR(20)       NULL,                    -- vacío si el producto salió sin lote
    fecha_vencimiento DATE              NULL,
    cantidad_enviada  DECIMAL(10,3)     NOT NULL,
    cantidad_recibida DECIMAL(10,3)     NULL,                    -- NULL mientras está en camino
    CONSTRAINT pk_traslado_detalle PRIMARY KEY (id_detalle),
    CONSTRAINT ck_trasdet_enviada CHECK (cantidad_enviada > 0),
    CONSTRAINT ck_trasdet_recibida CHECK (cantidad_recibida IS NULL OR (cantidad_recibida >= 0 AND cantidad_recibida <= cantidad_enviada)),
    CONSTRAINT fk_trasdet_traslado FOREIGN KEY (id_traslado) REFERENCES traslado_mercaderia (id_traslado),
    CONSTRAINT fk_trasdet_producto FOREIGN KEY (id_producto) REFERENCES producto (id_producto),
    KEY ix_trasdet_traslado (id_traslado)
) ENGINE=InnoDB;
