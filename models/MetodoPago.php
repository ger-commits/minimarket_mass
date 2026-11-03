<?php
// M: tabla metodo_pago: método de pago: EFECTIVO, TARJETA o BILLETERA DIGITAL SIP.
class MetodoPago
{
    public function __construct(
        public int $idMetodoPago,
        public string $nombreMetodo
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_metodo_pago'],
            (string)$fila['nombre_metodo']
        );
    }
}
