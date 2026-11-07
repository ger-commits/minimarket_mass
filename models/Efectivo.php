<?php
// M: tabla efectivo: detalle de un pago en efectivo (monto recibido y vuelto).
class Efectivo
{
    public function __construct(
        public int $idPago,
        public float $montoRecibido,
        public float $vuelto
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_pago'],
            (float)$fila['monto_recibido'],
            (float)$fila['vuelto']
        );
    }

    // vuelto = monto recibido - monto del pago
    public static function calcularVuelto(float $montoRecibido, float $montoPago): float
    {
        return round(max(0, $montoRecibido - $montoPago), 2);
    }
}
