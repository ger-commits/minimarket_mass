<?php
// M: tabla venta. Serie + correlativo no se repiten. Nunca se borra: EMITIDA o ANULADA.
class Venta
{
    public function __construct(
        public ?int $idVenta,
        public int $idSerie,
        public int $numeroCorrelativo,
        public int $idCajaTurno,
        public ?int $idCliente,     // NULL en boleta sin cliente; la factura exige cliente con RUC
        public array $lineas = [],  // cada línea: id_producto, cantidad, precio_unitario, importe
        public string $estado = 'EMITIDA'
    ) {}

    public function total(): float
    {
        return round(array_sum(array_column($this->lineas, 'importe')), 2);
    }

    // los pagos (efectivo, tarjeta, Sip) deben sumar exactamente el total
    public function pagosCuadran(array $montos): bool
    {
        return abs(array_sum($montos) - $this->total()) < 0.005;
    }
}
