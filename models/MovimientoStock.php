<?php
// M: tabla movimiento_stock: kardex: cada cambio de stock con exactamente un origen (venta, traslado, merma o devolución).
class MovimientoStock
{
    public function __construct(
        public int $idMovimiento,
        public string $fechaHora,
        public float $cantidad,
        public float $stockNuevo,   // saldo despues del movimiento
        public ?string $observacion,   // opcional
        public int $idTienda,
        public int $idProducto,
        public int $idTipoMovimiento,
        public int $idEmpleado,
        public ?int $idVenta,   // origen (uno solo)
        public ?int $idTraslado,   // origen (uno solo)
        public ?int $idMerma,   // origen (uno solo)
        public ?int $idDevolucion,   // origen (uno solo)
        public ?int $idLote   // lote afectado (solo productos con lote)
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_movimiento'],
            (string)$fila['fecha_hora'],
            (float)$fila['cantidad'],
            (float)$fila['stock_nuevo'],
            $fila['observacion'] === null ? null : (string)$fila['observacion'],
            (int)$fila['id_tienda'],
            (int)$fila['id_producto'],
            (int)$fila['id_tipo_movimiento'],
            (int)$fila['id_empleado'],
            $fila['id_venta'] === null ? null : (int)$fila['id_venta'],
            $fila['id_traslado'] === null ? null : (int)$fila['id_traslado'],
            $fila['id_merma'] === null ? null : (int)$fila['id_merma'],
            $fila['id_devolucion'] === null ? null : (int)$fila['id_devolucion'],
            $fila['id_lote'] === null ? null : (int)$fila['id_lote']
        );
    }

    // exactamente un origen: venta, traslado, merma o devolución
    public function origenUnico(): bool
    {
        return count(array_filter([$this->idVenta, $this->idTraslado, $this->idMerma, $this->idDevolucion], fn($v) => $v !== null)) === 1;
    }
}
