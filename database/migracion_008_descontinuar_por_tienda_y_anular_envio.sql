-- Migración segura (puede ejecutarse más de una vez). Requiere MySQL 8.0.16 o superior.
-- 1) Descontinuar por tienda: ahora "descontinuado" se guarda en el stock de cada tienda, así que el administrador
--    de Cayma solo descontinúa el producto en Cayma y no afecta a Cerro Colorado ni a Yanahuara.
-- 2) Anular un envío: un traslado EN_CAMINO puede pasar a ANULADO y el stock vuelve a la tienda de origen.
USE minimarket_mass;

DROP PROCEDURE IF EXISTS sp_migrar_008;
DELIMITER $$
CREATE PROCEDURE sp_migrar_008()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'stock' AND column_name = 'descontinuado'
    ) THEN
        ALTER TABLE stock ADD COLUMN descontinuado CHAR(1) NOT NULL DEFAULT 'N';
        ALTER TABLE stock ADD CONSTRAINT ck_stock_descontinuado CHECK (descontinuado IN ('S','N'));
    END IF;

    -- Los productos que antes estaban descontinuados para todos pasan a quedar descontinuados en cada tienda,
    -- y cada administrador podrá reactivarlos en la suya.
    UPDATE stock s JOIN producto p ON p.id_producto = s.id_producto
       SET s.descontinuado = 'S'
     WHERE p.estado = 'DESCONTINUADO';
    UPDATE producto SET estado = 'ACTIVO' WHERE estado = 'DESCONTINUADO';

    IF EXISTS (
        SELECT 1 FROM information_schema.table_constraints
         WHERE table_schema = DATABASE() AND table_name = 'traslado_mercaderia' AND constraint_name = 'ck_traslado_estado'
    ) THEN
        ALTER TABLE traslado_mercaderia DROP CHECK ck_traslado_estado;
    END IF;
    ALTER TABLE traslado_mercaderia
        ADD CONSTRAINT ck_traslado_estado CHECK (estado IN ('EN_CAMINO','RECIBIDO','ANULADO'));
END$$
DELIMITER ;

CALL sp_migrar_008();
DROP PROCEDURE sp_migrar_008;
