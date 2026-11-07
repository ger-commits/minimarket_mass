<?php
// Consultas del inventario, lotes y kardex limitadas a la tienda de la sesión.
require_once __DIR__ . '/Database.php';

class StockRepository
{
    public function porTienda(int $idTienda): array
    {
        $st = Database::conectar()->prepare(
            "SELECT p.id_producto, p.nombre_producto, p.se_vende_por_peso, um.abreviatura,
                    s.cantidad_actual, s.stock_minimo,
                    IF(s.cantidad_actual <= s.stock_minimo, 'REPONER', 'OK') AS situacion
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
               JOIN unidad_medida um ON um.id_unidad = p.id_unidad
              WHERE s.id_tienda = ? AND p.estado = 'ACTIVO' AND s.descontinuado = 'N'
              ORDER BY p.nombre_producto"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    public function productosActivos(): array
    {
        return Database::conectar()->query(
            "SELECT id_producto, nombre_producto, se_vende_por_peso FROM producto
              WHERE estado = 'ACTIVO' ORDER BY nombre_producto"
        )->fetchAll();
    }

    public function lotesPorTienda(int $idTienda): array
    {
        $st = Database::conectar()->prepare(
            "SELECT l.id_lote, l.codigo_lote, l.fecha_vencimiento, l.cantidad_actual,
                    p.nombre_producto, um.abreviatura
               FROM lote_producto l JOIN producto p ON p.id_producto = l.id_producto
               JOIN unidad_medida um ON um.id_unidad = p.id_unidad
              WHERE l.id_tienda = ? AND l.cantidad_actual > 0
              ORDER BY l.fecha_vencimiento, p.nombre_producto"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    public function movimientosPorTienda(int $idTienda, int $limite = 50): array
    {
        $st = Database::conectar()->prepare(
            "SELECT m.fecha_hora, p.nombre_producto, tm.nombre_movimiento, tm.efecto,
                    m.cantidad, m.stock_nuevo, m.observacion,
                    CONCAT(e.nombres, ' ', e.apellidos) AS empleado
               FROM movimiento_stock m
               JOIN producto p ON p.id_producto = m.id_producto
               JOIN tipo_movimiento tm ON tm.id_tipo_movimiento = m.id_tipo_movimiento
               JOIN empleado e ON e.id_empleado = m.id_empleado
              WHERE m.id_tienda = ?
              ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
              LIMIT " . (int)$limite
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }
}
