<?php
// M: tabla devolucion_venta: devolución parcial de productos de una venta emitida.
class DevolucionVenta
{
    public function __construct(
        public int $idDevolucion,
        public string $fechaHora,
        public string $motivo,
        public float $montoDevuelto,   // suma de los importes devueltos
        public int $idVenta
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_devolucion'],
            (string)$fila['fecha_hora'],
            (string)$fila['motivo'],
            (float)$fila['monto_devuelto'],
            (int)$fila['id_venta']
        );
    }
}
