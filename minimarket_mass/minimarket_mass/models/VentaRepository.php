<?php
// M: datos de ventas y de la boleta (usa la vista y el procedimiento de database/boleta.sql)
require_once __DIR__ . '/Database.php';

class VentaRepository
{
    public function boleta(string $serie, int $correlativo): array
    {
        $st = Database::conectar()->prepare(
            "SELECT comprobante, tipo_comprobante, fecha_hora, estado, tienda, caja, cajero, cliente,
                    item, producto, unidad, cantidad, precio_unitario, importe, total
               FROM vw_comprobante WHERE serie = ? AND numero_correlativo = ? ORDER BY item"
        );
        $st->execute([$serie, $correlativo]);
        return $st->fetchAll();
    }

    public function ventasPorDia(): array
    {
        return Database::conectar()->query(
            "SELECT DATE(fecha_hora) AS dia, COUNT(*) AS comprobantes, SUM(total) AS total
               FROM venta WHERE estado = 'EMITIDA' GROUP BY DATE(fecha_hora) ORDER BY dia"
        )->fetchAll();
    }
}
