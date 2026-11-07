<?php
// M: tabla merma: cabecera de una pérdida de mercadería; su tienda sale del empleado que la registra.
class Merma
{
    public function __construct(
        public int $idMerma,
        public string $fechaHora,
        public string $motivo,
        public ?string $observacion,   // opcional
        public int $idEmpleado   // su tienda sale del empleado
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_merma'],
            (string)$fila['fecha_hora'],
            (string)$fila['motivo'],
            $fila['observacion'] === null ? null : (string)$fila['observacion'],
            (int)$fila['id_empleado']
        );
    }
}
