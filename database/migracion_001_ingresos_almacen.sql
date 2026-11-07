-- Migración segura para una base minimarket_mass ya existente.
-- Puede ejecutarse si el campo/tabla ya existen: comprueba antes de agregarlos.
-- Requiere MySQL 8.0.16 o superior.
USE minimarket_mass;

DROP PROCEDURE IF EXISTS sp_migrar_ingresos_almacen;
DELIMITER $$
CREATE PROCEDURE sp_migrar_ingresos_almacen()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'ingreso_mercaderia'
    ) THEN
        CREATE TABLE ingreso_mercaderia (
            id_ingreso  INT UNSIGNED       NOT NULL AUTO_INCREMENT,
            fecha_hora  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
            observacion VARCHAR(200)       NULL,
            id_empleado MEDIUMINT UNSIGNED NOT NULL,
            CONSTRAINT pk_ingreso PRIMARY KEY (id_ingreso),
            CONSTRAINT fk_ingreso_empleado FOREIGN KEY (id_empleado) REFERENCES empleado (id_empleado)
        ) ENGINE=InnoDB;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'movimiento_stock' AND column_name = 'id_ingreso'
    ) THEN
        ALTER TABLE movimiento_stock ADD COLUMN id_ingreso INT UNSIGNED NULL AFTER id_devolucion;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.table_constraints
         WHERE constraint_schema = DATABASE() AND table_name = 'movimiento_stock'
           AND constraint_name = 'ck_movimiento_origen' AND constraint_type = 'CHECK'
    ) THEN
        ALTER TABLE movimiento_stock DROP CHECK ck_movimiento_origen;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.table_constraints
         WHERE constraint_schema = DATABASE() AND table_name = 'movimiento_stock'
           AND constraint_name = 'fk_movimiento_ingreso' AND constraint_type = 'FOREIGN KEY'
    ) THEN
        ALTER TABLE movimiento_stock DROP FOREIGN KEY fk_movimiento_ingreso;
    END IF;

    ALTER TABLE movimiento_stock
        ADD CONSTRAINT ck_movimiento_origen CHECK (
            (id_venta IS NOT NULL) + (id_traslado IS NOT NULL) + (id_merma IS NOT NULL) +
            (id_devolucion IS NOT NULL) + (id_ingreso IS NOT NULL) = 1
        ),
        ADD CONSTRAINT fk_movimiento_ingreso FOREIGN KEY (id_ingreso)
            REFERENCES ingreso_mercaderia (id_ingreso);

    INSERT INTO tipo_movimiento (id_tipo_movimiento, nombre_movimiento, efecto)
    VALUES (7, 'INGRESO DE MERCADERIA', 'E')
    ON DUPLICATE KEY UPDATE nombre_movimiento = VALUES(nombre_movimiento), efecto = VALUES(efecto);
END$$
DELIMITER ;

CALL sp_migrar_ingresos_almacen();
DROP PROCEDURE sp_migrar_ingresos_almacen;
