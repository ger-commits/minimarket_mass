<?php
// M: tabla detalle_devolucion: línea de una devolución (línea vendida y cantidad devuelta).
class DetalleDevolucion
{
    public function __construct(
        public int $idDetalleDevolucion,
        public float $cantidad,
        public float $importe,   // cantidad x precio_unitario de la linea vendida
        public int $idDevolucion,
        public int $idDetalleVenta
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_detalle_devolucion'],
            (float)$fila['cantidad'],
            (float)$fila['importe'],
            (int)$fila['id_devolucion'],
            (int)$fila['id_detalle_venta']
        );
    }
}
