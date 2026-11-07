<?php
// M: tabla caja: caja registradora de una tienda.
class Caja
{
    public function __construct(
        public int $idCaja,
        public int $numeroCaja,
        public string $estado,
        public int $idTienda
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_caja'],
            (int)$fila['numero_caja'],
            (string)$fila['estado'],
            (int)$fila['id_tienda']
        );
    }
}
