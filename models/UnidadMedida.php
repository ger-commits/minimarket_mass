<?php
// M: tabla unidad_medida: unidad de medida del producto (kilo, unidad...).
class UnidadMedida
{
    public function __construct(
        public int $idUnidad,
        public string $abreviatura,
        public string $nombreUnidad
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_unidad'],
            (string)$fila['abreviatura'],
            (string)$fila['nombre_unidad']
        );
    }
}
