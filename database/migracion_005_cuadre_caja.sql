-- Migración segura para una base minimarket_mass ya existente: cuadre de caja.
-- Guarda, al cerrar el turno, el efectivo que el sistema esperaba y la observación del cajero.
-- Así la diferencia de un turno cerrado ya no cambia si después se anula una venta suya.
-- Puede ejecutarse más de una vez. Requiere MySQL 8.0.16 o superior.
USE minimarket_mass;

DROP PROCEDURE IF EXISTS sp_migrar_cuadre_caja;
DELIMITER $$
CREATE PROCEDURE sp_migrar_cuadre_caja()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'caja_turno' AND column_name = 'monto_esperado'
    ) THEN
        ALTER TABLE caja_turno ADD COLUMN monto_esperado DECIMAL(10,2) NULL AFTER monto_cierre;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'caja_turno' AND column_name = 'observacion_cierre'
    ) THEN
        ALTER TABLE caja_turno ADD COLUMN observacion_cierre VARCHAR(200) NULL AFTER monto_esperado;
    END IF;
END$$
DELIMITER ;

CALL sp_migrar_cuadre_caja();
DROP PROCEDURE sp_migrar_cuadre_caja;
