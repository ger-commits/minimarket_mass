<?php
// M: tabla marca: marca del producto.
class Marca
{
    public function __construct(
        public int $idMarca,
        public string $nombreMarca,
        public string $esMarcaPropia
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_marca'],
            (string)$fila['nombre_marca'],
            (string)$fila['es_marca_propia']
        );
    }
}
