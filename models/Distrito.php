<?php
// M: tabla distrito: distrito de Arequipa donde hay tiendas (Cayma, Cerro Colorado, Yanahuara).
class Distrito
{
    public function __construct(
        public int $idDistrito,
        public string $nombreDistrito
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_distrito'],
            (string)$fila['nombre_distrito']
        );
    }
}
