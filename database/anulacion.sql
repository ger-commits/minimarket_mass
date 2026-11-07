/* =====================================================================
   MINIMARKET MASS - anulacion.sql
   Tablas que necesita la anulacion de ventas "de verdad":
     - serie_nota_credito : series de notas de credito por tienda (BC01, FC01...) y su ultimo correlativo.
     - nota_credito       : la nota de credito que se emite al AUTORIZAR una anulacion (una por venta).
     - registro_anulacion_central : registro de la anulacion en el "Sistema central".
   Ejecutar DESPUES de schema.sql y datos.sql (se puede ejecutar mas de una vez).
   ===================================================================== */

USE minimarket_mass;
SET NAMES utf8mb4;

-- Series de notas de credito. Una por tienda y por tipo de comprobante que se anula
-- (nota de credito de boleta = serie BC.., de factura = serie FC..).
CREATE TABLE IF NOT EXISTS serie_nota_credito (
    id_serie_nc         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    serie               CHAR(4)           NOT NULL,              -- BC01, FC01...
    ultimo_numero       INT UNSIGNED      NOT NULL DEFAULT 0,    -- ultimo correlativo emitido
    id_tienda           SMALLINT UNSIGNED NOT NULL,
    id_tipo_comprobante TINYINT UNSIGNED  NOT NULL,              -- tipo del comprobante que modifica
    CONSTRAINT pk_serie_nc PRIMARY KEY (id_serie_nc),
    CONSTRAINT uq_serie_nc_serie UNIQUE (serie),
    CONSTRAINT uq_serie_nc_tienda_tipo UNIQUE (id_tienda, id_tipo_comprobante),
    CONSTRAINT ck_serie_nc_formato CHECK (serie REGEXP '^[BF]C[0-9]{2}$'),
    CONSTRAINT ck_serie_nc_ultimo CHECK (ultimo_numero <= 99999999),
    CONSTRAINT fk_serie_nc_tienda FOREIGN KEY (id_tienda) REFERENCES tienda (id_tienda),
    CONSTRAINT fk_serie_nc_tipo FOREIGN KEY (id_tipo_comprobante) REFERENCES tipo_comprobante (id_tipo_comprobante)
) ENGINE=InnoDB;

-- Nota de credito: nace al aprobarse la solicitud de anulacion. Una sola por venta y una sola por solicitud.
CREATE TABLE IF NOT EXISTS nota_credito (
    id_nota_credito    INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    numero_correlativo INT UNSIGNED      NOT NULL,
    fecha_hora         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo             VARCHAR(150)      NOT NULL,
    monto              DECIMAL(10,2)     NOT NULL,               -- total de la venta anulada
    id_serie_nc        SMALLINT UNSIGNED NOT NULL,
    id_venta           BIGINT UNSIGNED   NOT NULL,
    id_solicitud       INT UNSIGNED      NOT NULL,
    CONSTRAINT pk_nota_credito PRIMARY KEY (id_nota_credito),
    CONSTRAINT uq_nota_credito_numero UNIQUE (id_serie_nc, numero_correlativo),
    CONSTRAINT uq_nota_credito_venta UNIQUE (id_venta),
    CONSTRAINT uq_nota_credito_solicitud UNIQUE (id_solicitud),
    CONSTRAINT ck_nota_credito_correlativo CHECK (numero_correlativo BETWEEN 1 AND 99999999),
    CONSTRAINT ck_nota_credito_monto CHECK (monto > 0),
    CONSTRAINT fk_nota_credito_serie FOREIGN KEY (id_serie_nc) REFERENCES serie_nota_credito (id_serie_nc),
    CONSTRAINT fk_nota_credito_venta FOREIGN KEY (id_venta) REFERENCES venta (id_venta),
    CONSTRAINT fk_nota_credito_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitud_anulacion (id_solicitud)
) ENGINE=InnoDB;

-- Registro de la anulacion en el Sistema central (se escribe en la misma transaccion que la anulacion).
CREATE TABLE IF NOT EXISTS registro_anulacion_central (
    id_registro         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    codigo_central      VARCHAR(30)     NOT NULL,                -- ANU-AAAAMMDD-000001
    fecha_hora_registro DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado              VARCHAR(12)     NOT NULL DEFAULT 'REGISTRADO',
    detalle             JSON            NOT NULL,                -- copia de lo que se informo al sistema central
    id_solicitud        INT UNSIGNED    NOT NULL,
    id_venta            BIGINT UNSIGNED NOT NULL,
    id_nota_credito     INT UNSIGNED    NOT NULL,
    CONSTRAINT pk_registro_central PRIMARY KEY (id_registro),
    CONSTRAINT uq_registro_central_codigo UNIQUE (codigo_central),
    CONSTRAINT uq_registro_central_solicitud UNIQUE (id_solicitud),
    CONSTRAINT ck_registro_central_estado CHECK (estado IN ('REGISTRADO','OBSERVADO')),
    CONSTRAINT fk_registro_central_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitud_anulacion (id_solicitud),
    CONSTRAINT fk_registro_central_venta FOREIGN KEY (id_venta) REFERENCES venta (id_venta),
    CONSTRAINT fk_registro_central_nota FOREIGN KEY (id_nota_credito) REFERENCES nota_credito (id_nota_credito)
) ENGINE=InnoDB;

-- Series de notas de credito: tiendas 1 = Cayma, 2 = Cerro Colorado, 3 = Yanahuara (tipo 1 = boleta, 2 = factura)
INSERT IGNORE INTO serie_nota_credito (id_serie_nc, serie, ultimo_numero, id_tienda, id_tipo_comprobante) VALUES
    (1, 'BC01', 0, 1, 1),
    (2, 'FC01', 0, 1, 2),
    (3, 'BC02', 0, 2, 1),
    (4, 'FC02', 0, 2, 2),
    (5, 'BC03', 0, 3, 1),
    (6, 'FC03', 0, 3, 2);

-- La venta 4 ya estaba ANULADA en los datos de prueba (solicitud 1 aprobada): se le emite su nota de credito
-- para que el historial quede completo.
INSERT IGNORE INTO nota_credito (id_nota_credito, numero_correlativo, fecha_hora, motivo, monto, id_serie_nc, id_venta, id_solicitud)
SELECT 1, 1, sa.fecha_hora_resolucion, sa.motivo, v.total, 1, v.id_venta, sa.id_solicitud
  FROM solicitud_anulacion sa JOIN venta v ON v.id_venta = sa.id_venta
 WHERE sa.id_solicitud = 1 AND sa.estado = 'APROBADA' AND v.id_venta = 4;

UPDATE serie_nota_credito SET ultimo_numero = 1
 WHERE id_serie_nc = 1 AND ultimo_numero = 0 AND EXISTS (SELECT 1 FROM nota_credito WHERE id_nota_credito = 1);

INSERT IGNORE INTO registro_anulacion_central (id_registro, codigo_central, fecha_hora_registro, estado, detalle, id_solicitud, id_venta, id_nota_credito)
SELECT 1, 'ANU-20261017-000001', nc.fecha_hora, 'REGISTRADO',
       JSON_OBJECT('comprobante', 'B001-000002', 'nota_credito', 'BC01-000001', 'monto', nc.monto, 'motivo', nc.motivo),
       nc.id_solicitud, nc.id_venta, nc.id_nota_credito
  FROM nota_credito nc WHERE nc.id_nota_credito = 1;
