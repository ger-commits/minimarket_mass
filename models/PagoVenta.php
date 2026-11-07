<?php
// M: tabla pago_venta: pago de una venta (puede haber varios y suman el total).
class PagoVenta
{
    public function __construct(
        public int $idPago,
        public float $montoPagado,
        public string $fechaHora,
        public string $estado,
        public int $idVenta,
        public int $idMetodoPago
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_pago'],
            (float)$fila['monto_pagado'],
            (string)$fila['fecha_hora'],
            (string)$fila['estado'],
            (int)$fila['id_venta'],
            (int)$fila['id_metodo_pago']
        );
    }

    public function esValido(): bool { return $this->estado === 'VALIDO'; }
}
