<?php
// M: tabla tienda: cada una de las sucursales de Mass (hasta 3).
class Tienda
{
    public function __construct(
        public int $idTienda,
        public string $nombreTienda,
        public string $direccion,
        public ?string $telefono,   // opcional
        public string $estado,
        public int $idDistrito
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_tienda'],
            (string)$fila['nombre_tienda'],
            (string)$fila['direccion'],
            $fila['telefono'] === null ? null : (string)$fila['telefono'],
            (string)$fila['estado'],
            (int)$fila['id_distrito']
        );
    }
}
