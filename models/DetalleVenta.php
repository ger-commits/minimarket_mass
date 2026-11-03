<?php
// M: tabla detalle_venta: línea de una venta (producto, cantidad y copia del precio).
class DetalleVenta
{
    public function __construct(
        public int $idDetalleVenta,
        public float $cantidad,
        public float $precioUnitario,   // copia del precio al momento de vender
        public float $importe,
        public int $idVenta,
        public int $idProducto
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_detalle_venta'],
            (float)$fila['cantidad'],
            (float)$fila['precio_unitario'],
            (float)$fila['importe'],
            (int)$fila['id_venta'],
            (int)$fila['id_producto']
        );
    }

    // importe = cantidad x precio_unitario, redondeado a 2 decimales
    public static function calcularImporte(float $cantidad, float $precioUnitario): float
    {
        return round($cantidad * $precioUnitario, 2);
    }

    public function importeCorrecto(): bool { return abs($this->importe - self::calcularImporte($this->cantidad, $this->precioUnitario)) < 0.005; }
}
