<?php
// M: tabla producto (precio único para las 3 tiendas; se vende por unidad o por peso)
class Producto
{
    public function __construct(
        public int $idProducto,
        public string $nombre,
        public float $precioVenta,
        public bool $seVendePorPeso,
        public string $estado
    ) {}

    // Por unidad: cantidad entera. Por peso: hasta 3 decimales (kilos).
    public function cantidadValida(float $cantidad): bool
    {
        if ($cantidad <= 0) return false;
        return $this->seVendePorPeso ? true : floor($cantidad) == $cantidad;
    }

    // importe de la línea = cantidad x precio (el total de la venta es la suma de importes)
    public function importe(float $cantidad): float
    {
        return round($cantidad * $this->precioVenta, 2);
    }
}
