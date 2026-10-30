<?php
// M: de dónde salen los datos del catálogo (producto + categoría + marca + unidad)
require_once __DIR__ . '/Database.php';

class ProductoRepository
{
    public function listar(): array
    {
        return Database::conectar()->query(
            "SELECT p.id_producto, p.nombre_producto, c.nombre_categoria, m.nombre_marca,
                    u.abreviatura, p.precio_venta, p.estado
               FROM producto p
               JOIN categoria c    ON c.id_categoria = p.id_categoria
               JOIN marca m        ON m.id_marca = p.id_marca
               JOIN unidad_medida u ON u.id_unidad = p.id_unidad
              ORDER BY c.nombre_categoria, p.nombre_producto"
        )->fetchAll();
    }

    public function buscar(int $idProducto): ?array
    {
        $st = Database::conectar()->prepare('SELECT * FROM producto WHERE id_producto = ?');
        $st->execute([$idProducto]);
        return $st->fetch() ?: null;
    }
}
