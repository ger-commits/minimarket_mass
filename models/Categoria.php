<?php
// M: tabla categoria: categoría del producto.
class Categoria
{
    public function __construct(
        public int $idCategoria,
        public string $nombreCategoria
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_categoria'],
            (string)$fila['nombre_categoria']
        );
    }
}
