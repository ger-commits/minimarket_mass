<?php
// M: tabla tarjeta: detalle de un pago con tarjeta (solo los últimos 4 dígitos).
class Tarjeta
{
    public function __construct(
        public int $idPago,
        public string $tipoTarjeta,
        public string $marcaTarjeta,
        public string $ultimos4Digitos,   // nunca se guarda el numero completo
        public string $codigoAutorizacion
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_pago'],
            (string)$fila['tipo_tarjeta'],
            (string)$fila['marca_tarjeta'],
            (string)$fila['ultimos_4_digitos'],
            (string)$fila['codigo_autorizacion']
        );
    }

    // solo se guardan los últimos 4 dígitos, nunca el número completo
    public function ultimosDigitosValidos(): bool { return (bool)preg_match('/^[0-9]{4}$/', $this->ultimos4Digitos); }
}
