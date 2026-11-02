<?php
// M: stock por tienda (clave compuesta tienda + producto). Nunca puede ser negativo.
require_once __DIR__ . '/Database.php';

class StockRepository
{
    public function porTienda(int $idTienda): array
    {
        $st = Database::conectar()->prepare(
            "SELECT p.nombre_producto, s.cantidad_actual, s.stock_minimo,
                    IF(s.cantidad_actual <= s.stock_minimo, 'REPONER', 'OK') AS situacion
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
              WHERE s.id_tienda = ?
              ORDER BY p.nombre_producto"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }
}
