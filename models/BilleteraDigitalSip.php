<?php
// M: tabla billetera_digital_sip: detalle de un pago con la billetera digital Sip.
class BilleteraDigitalSip
{
    public function __construct(
        public int $idPago,
        public string $modalidadPago,
        public string $codigoOperacion
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_pago'],
            (string)$fila['modalidad_pago'],
            (string)$fila['codigo_operacion']
        );
    }
}
