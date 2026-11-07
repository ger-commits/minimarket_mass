<?php
// M: tabla tipo_comprobante: tipo de comprobante: BOLETA (serie B) o FACTURA (serie F).
class TipoComprobante
{
    public function __construct(
        public int $idTipoComprobante,
        public string $nombreComprobante,
        public string $letraSerie   // B = boleta, F = factura
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_tipo_comprobante'],
            (string)$fila['nombre_comprobante'],
            (string)$fila['letra_serie']
        );
    }
}
