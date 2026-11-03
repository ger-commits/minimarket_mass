<?php
// M: tabla tipo_movimiento: tipo de movimiento del kardex y su efecto (E = entrada, S = salida).
class TipoMovimiento
{
    public function __construct(
        public int $idTipoMovimiento,
        public string $nombreMovimiento,
        public string $efecto   // E = entrada, S = salida
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_tipo_movimiento'],
            (string)$fila['nombre_movimiento'],
            (string)$fila['efecto']
        );
    }
}
