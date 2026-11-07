<?php
// M: tabla rol: rol del empleado: ADMINISTRADOR, CAJERO o ALMACENERO.
class Rol
{
    public function __construct(
        public int $idRol,
        public string $nombreRol
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_rol'],
            (string)$fila['nombre_rol']
        );
    }
}
