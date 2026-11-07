-- Migración 004: avisos para el almacén (por ejemplo, "producto eliminado").
-- Ejecutar una sola vez. No modifica tablas existentes.
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
