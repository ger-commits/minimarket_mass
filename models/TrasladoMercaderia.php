<?php
// M: tabla traslado_mercaderia: cabecera de un traslado de mercadería entre dos tiendas; lo registra el administrador de la tienda de origen.
class TrasladoMercaderia
{
    public function __construct(
        public int $idTraslado,
        public string $fechaHora,
        public ?string $observacion,   // opcional
        public int $idTiendaOrigen,
        public int $idTiendaDestino,
        public int $idEmpleado
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_traslado'],
            (string)$fila['fecha_hora'],
            $fila['observacion'] === null ? null : (string)$fila['observacion'],
            (int)$fila['id_tienda_origen'],
            (int)$fila['id_tienda_destino'],
            (int)$fila['id_empleado']
        );
    }

    // origen y destino deben ser tiendas distintas
    public function tiendasValidas(): bool { return $this->idTiendaOrigen !== $this->idTiendaDestino; }
}
